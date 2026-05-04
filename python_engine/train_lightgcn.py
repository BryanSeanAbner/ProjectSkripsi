"""
LightGCN Model — implementasi dengan PyTorch Geometric
────────────────────────────────────────────────────────
Training menggunakan FULL interactions (acu_interactions_customized5.csv).
Internal 90/10 train/val split hanya untuk monitoring BPR loss.

Output tabel: recommendation_history
  - user_id, article_id, rank_position, generated_at
"""

import sys
import os
import pickle
import numpy as np
import pandas as pd
import torch
import torch.nn as nn
import torch.nn.functional as F
from datetime import datetime
from collections import defaultdict
from sklearn.preprocessing import LabelEncoder
from torch_geometric.nn import LGConv

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import INTERACTIONS_CSV, SAVED_MODELS_DIR, TOP_K
from db_client import supabase_client

# ─── Config ───────────────────────────────────────────────────────────────────
LGCN_WEIGHTS_PATH  = os.path.join(SAVED_MODELS_DIR, "best_model.pt")
LGCN_MAPPINGS_PATH = os.path.join(SAVED_MODELS_DIR, "lightgcn_mappings.pkl")

# Hyperparameters (sesuai notebook Colab user)
EMB_DIM      = 8
N_LAYERS     = 3
EPOCHS       = 500
STEPS        = 10
BATCH_SIZE   = 1024
LR           = 5e-4
WEIGHT_DECAY = 1e-4
SEED         = 42
DROPOUT      = 0.5
GAMMA        = 0.1
MAX_PER_ITEM = 40
ALPHA        = 0.8

device = torch.device("cpu")


# ══════════════════════════════════════════════════════════════════════════════
#  Model Definition
# ══════════════════════════════════════════════════════════════════════════════

class LightGCN(nn.Module):
    def __init__(self, n_users, n_items, emb_dim, n_layers, edge_index, dropout):
        super().__init__()
        self.n_users    = n_users
        self.edge_index = edge_index
        self.dropout    = nn.Dropout(dropout)

        self.emb_user = nn.Embedding(n_users, emb_dim)
        self.emb_item = nn.Embedding(n_items, emb_dim)
        nn.init.xavier_uniform_(self.emb_user.weight)
        nn.init.xavier_uniform_(self.emb_item.weight)

        self.convs = nn.ModuleList([LGConv() for _ in range(n_layers)])

    def forward(self):
        x = self.dropout(torch.cat([self.emb_user.weight, self.emb_item.weight], dim=0))
        layers = [x]
        for conv in self.convs:
            x = conv(x, self.edge_index)
            layers.append(x)
        out = torch.stack(layers, dim=1).mean(dim=1)
        return out[:self.n_users], out[self.n_users:]


def bpr_loss(model, u_emb, i_emb, users, pos, neg):
    pos_s = (u_emb[users] * i_emb[pos]).sum(1)
    neg_s = (u_emb[users] * i_emb[neg]).sum(1)
    mf    = -F.logsigmoid(pos_s - neg_s).mean()
    reg   = (model.emb_user(users).norm(2).pow(2) +
             model.emb_item(pos).norm(2).pow(2) +
             model.emb_item(neg).norm(2).pow(2)) / len(users)
    return mf + WEIGHT_DECAY * reg


# ══════════════════════════════════════════════════════════════════════════════
#  1. TRAINING
# ══════════════════════════════════════════════════════════════════════════════

def build_graph(df, n_users):
    u   = torch.tensor(df["user_idx"].values)
    i   = torch.tensor(df["item_idx"].values) + n_users
    src = torch.cat([u, i])
    dst = torch.cat([i, u])
    return torch.stack([src, dst]).to(device)


def sample_neg_from(u, pos_dict, n_items):
    while True:
        neg = np.random.randint(n_items)
        if neg not in pos_dict[u]:
            return neg


def compute_balanced_df(df):
    parts = []
    for _, grp in df.groupby("item_idx"):
        if len(grp) > MAX_PER_ITEM:
            grp = grp.sample(MAX_PER_ITEM, random_state=np.random.randint(10000))
        parts.append(grp)
    return pd.concat(parts).reset_index(drop=True)


def train(epochs: int = EPOCHS, embedding_dim: int = EMB_DIM,
          num_layers: int = N_LAYERS) -> None:

    print("[LightGCN] Memuat interaksi dari CSV (full interactions — 100% data)...")
    full_df = pd.read_csv(INTERACTIONS_CSV)

    np.random.seed(SEED)

    # Encoding — gunakan SELURUH data
    user_enc = LabelEncoder()
    item_enc = LabelEncoder()
    user_enc.fit(full_df["user_id"])
    item_enc.fit(full_df["article_id"])

    def encode_df(df):
        df = df[
            df["user_id"].isin(user_enc.classes_) &
            df["article_id"].isin(item_enc.classes_)
        ].copy()
        df["user_idx"] = user_enc.transform(df["user_id"])
        df["item_idx"] = item_enc.transform(df["article_id"])
        return df

    train_df = encode_df(full_df).drop_duplicates(["user_idx", "item_idx"])

    n_users = len(user_enc.classes_)
    n_items = len(item_enc.classes_)
    print(f"[LightGCN] n_users={n_users} | n_items={n_items}")
    print(f"[LightGCN] Config: gamma={GAMMA}, max_per_item={MAX_PER_ITEM}, "
          f"dropout={DROPOUT}, alpha={ALPHA}")

    # Popularity stats
    pop_counts = np.zeros(n_items, dtype=np.float32)
    for item_idx, cnt in train_df["item_idx"].value_counts().items():
        pop_counts[int(item_idx)] = cnt

    neg_sampling_probs = np.power(pop_counts + 1, GAMMA)
    neg_sampling_probs /= neg_sampling_probs.sum()

    pop_score = torch.tensor(
        np.log1p(pop_counts) / (np.log1p(pop_counts).max() + 1e-8),
        dtype=torch.float32
    ).to(device)

    # Graph — dari 100% data
    edge_index = build_graph(train_df, n_users)

    # Positive sets
    user_pos = defaultdict(set)
    for _, r in train_df.iterrows():
        user_pos[int(r["user_idx"])].add(int(r["item_idx"]))

    # Sampling
    def sample_batch(balanced_df):
        batch = balanced_df.sample(BATCH_SIZE, replace=(len(balanced_df) < BATCH_SIZE))
        users     = torch.LongTensor(batch["user_idx"].values).to(device)
        pos_items = torch.LongTensor(batch["item_idx"].values).to(device)
        neg_items = torch.LongTensor(
            np.random.choice(n_items, size=BATCH_SIZE, p=neg_sampling_probs)
        ).to(device)
        return users, pos_items, neg_items

    # Model
    model = LightGCN(n_users, n_items, embedding_dim, num_layers, edge_index, DROPOUT).to(device)
    optimizer = torch.optim.Adam(model.parameters(), lr=LR)

    best_loss = float("inf")

    print("[LightGCN] Training (100% data, tanpa val split)...")
    for epoch in range(1, epochs + 1):
        model.train()
        balanced_df = compute_balanced_df(train_df)
        total_loss  = 0.0

        for _ in range(STEPS):
            u_emb, i_emb = model()
            u, p, n_s    = sample_batch(balanced_df)
            loss         = bpr_loss(model, u_emb, i_emb, u, p, n_s)
            optimizer.zero_grad()
            loss.backward()
            optimizer.step()
            total_loss += loss.item()

        avg_loss = total_loss / STEPS
        print(f"Epoch {epoch:3d} | train_loss {avg_loss:.4f}")

        if avg_loss < best_loss:
            best_loss = avg_loss
            torch.save(model.state_dict(), LGCN_WEIGHTS_PATH)

    # Simpan mappings untuk inference
    def build_gt(df):
        gt = defaultdict(set)
        for _, r in df.iterrows():
            gt[int(r["user_idx"])].add(int(r["item_idx"]))
        return gt

    all_gt = build_gt(train_df)

    with open(LGCN_MAPPINGS_PATH, "wb") as f:
        pickle.dump({
            "user_enc": user_enc,
            "item_enc": item_enc,
            "pop_score": pop_score.cpu(),
            "edge_index": edge_index.cpu(),
            "train_gt": all_gt,
            "val_gt": {},          # Kosong — tidak ada val split
            "n_users": n_users,
            "n_items": n_items,
            "embedding_dim": embedding_dim,
            "num_layers": num_layers,
            "dropout": DROPOUT
        }, f)

    print(f"[LightGCN] Model tersimpan di {SAVED_MODELS_DIR}")


# ══════════════════════════════════════════════════════════════════════════════
#  2. INFERENCE
# ══════════════════════════════════════════════════════════════════════════════

def _load_model():
    with open(LGCN_MAPPINGS_PATH, "rb") as f:
        meta = pickle.load(f)

    model = LightGCN(
        meta["n_users"], meta["n_items"],
        meta["embedding_dim"], meta["num_layers"],
        meta["edge_index"].to(device), meta["dropout"]
    ).to(device)
    model.load_state_dict(torch.load(LGCN_WEIGHTS_PATH, map_location=device))
    model.eval()
    return model, meta


def generate_lightgcn_recs(user_id: int, k: int = TOP_K) -> list[dict]:
    try:
        model, meta = _load_model()
    except Exception as e:
        print("[LightGCN] Error loading model:", e)
        return []

    user_enc = meta["user_enc"]
    item_enc = meta["item_enc"]
    pop_score = meta["pop_score"].to(device)
    train_gt = meta["train_gt"]
    val_gt = meta["val_gt"]

    enc_type = type(user_enc.classes_[0])
    try:
        uid = enc_type(user_id)
    except:
        uid = user_id

    if uid not in user_enc.classes_:
        print(f"[LightGCN] user_id {uid} tidak ada di training data.")
        return []

    user_idx = int(user_enc.transform([uid])[0])

    with torch.no_grad():
        u_emb, i_emb = model()

    scores = (i_emb @ u_emb[user_idx]).clone()
    scores = scores - ALPHA * pop_score

    seen = train_gt.get(user_idx, set()) | val_gt.get(user_idx, set())
    if seen:
        scores[list(seen)] = float("-inf")

    top_k_idx = torch.topk(scores, k).indices.cpu().numpy()
    top_k_arts = item_enc.inverse_transform(top_k_idx)

    result = []
    for rank, art_id in enumerate(top_k_arts, start=1):
        result.append({
            "article_id":   int(art_id),
            "rank_position": rank,
        })
    return result


def save_lightgcn_recs(user_id: int, recs: list[dict]) -> None:
    if not recs:
        print(f"[LightGCN] Tidak ada rekomendasi untuk user {user_id}.")
        return

    client = supabase_client()
    client.table("recommendation_history").delete().eq("user_id", user_id).execute()

    rows = []
    for rec in recs:
        rows.append({
            "user_id":      int(user_id),
            "article_id":   rec["article_id"],
            "rank_position": rec["rank_position"],
            "generated_at": datetime.utcnow().isoformat(),
        })

    client.table("recommendation_history").insert(rows).execute()
    print(f"[LightGCN] User {user_id} -> {len(rows)} artikel ke recommendation_history.")


def run(user_id: int) -> None:
    recs = generate_lightgcn_recs(user_id)
    if recs:
        save_lightgcn_recs(user_id, recs)


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument("--train",        action="store_true")
    parser.add_argument("--user_id",      type=int, default=None)
    args = parser.parse_args()

    if args.train:
        train()
    elif args.user_id:
        run(args.user_id)
    else:
        print("Gunakan --train atau --user_id=<id>")
