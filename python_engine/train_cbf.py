"""
Content-Based Filtering (CBF) Model
─────────────────────────────────────
Menggunakan TF-IDF pada kolom `title` + `content` artikel
untuk menghitung kemiripan antar artikel (cosine similarity).

Tahap Training  → simpan matriks similarity ke file .pkl
Tahap Inference → berdasarkan histori user, cari referensi artikel
                  → rekomendasi top-k tersimpan ke `article_similarity`
"""

import sys
import os
import pickle
import numpy as np
import pandas as pd
import re
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from config import ARTICLE_CSV, INTERACTIONS_CSV, SAVED_MODELS_DIR, TOP_K
from db_client import supabase_client

# ─── Path model tersimpan ────────────────────────────────────────────────────
CBF_MATRIX_PATH  = os.path.join(SAVED_MODELS_DIR, "cbf_sim_matrix.pkl")
ARTICLE_INFO_PATH = os.path.join(SAVED_MODELS_DIR, "cbf_article_info.pkl")

STOPWORDS_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), "tala-stopwords-indonesia.txt")


# ===============================
# TEXT PREPROCESSING
# ===============================
def load_stopwords() -> set:
    if not os.path.exists(STOPWORDS_PATH):
        return set()
    with open(STOPWORDS_PATH, "r", encoding="utf-8") as f:
        return set(f.read().splitlines())

STOPWORDS_ID = load_stopwords()

def preprocess(text):
    if not isinstance(text, str):
        return ""
    text = text.lower()
    text = re.sub(r'http\S+', '', text)
    text = re.sub(r'[^a-zA-Z0-9\s]', '', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text

def remove_stopwords(text):
    return " ".join([w for w in text.split() if w not in STOPWORDS_ID])

def full_preprocess(text):
    return remove_stopwords(preprocess(text))

#  1. TRAINING

def train() -> None:
    """Latih TF-IDF dan simpan similarity matrix ke disk."""
    print("[CBF] Memuat artikel dari CSV (article_dataset5.csv)...")
    df = pd.read_csv(ARTICLE_CSV)
    df = df.reset_index(drop=True)
    print("[CBF] Preprocessing teks (menghapus stopwords & karakter khusus)...")
    df["clean_title"]   = df["title"].apply(full_preprocess)
    df["clean_content"] = df["content"].apply(full_preprocess)

    # Title diberi bobot lebih besar (dikalikan 3 space)
    df["text"] = (df["clean_title"] + " ") * 3 + df["clean_content"]

    print("[CBF] Menghitung TF-IDF & Cosine Similarity...")
    tfidf = TfidfVectorizer(max_features=10000, ngram_range=(1, 2))
    tfidf_matrix = tfidf.fit_transform(df["text"])
    cosine_sim   = cosine_similarity(tfidf_matrix, tfidf_matrix)

    # Simpan ke disk
    with open(CBF_MATRIX_PATH, "wb")  as f: pickle.dump(cosine_sim,  f)
    with open(ARTICLE_INFO_PATH, "wb") as f: pickle.dump(df, f)

    print(f"[CBF] Model tersimpan di {SAVED_MODELS_DIR}")


#  2. INFERENCE

def _load_model():
    with open(CBF_MATRIX_PATH,  "rb") as f: cosine_sim = pickle.load(f)
    with open(ARTICLE_INFO_PATH, "rb") as f: df = pickle.load(f)
    return cosine_sim, df

def _get_user_history(user_id: int) -> list[int]:
    """Ambil daftar article_id yang pernah diinteraksikan user dari interactions."""
    df = pd.read_csv(INTERACTIONS_CSV)
    user_hist = df[df["user_id"] == user_id]["article_id"].tolist()
    return user_hist


def generate_cbf_recs(user_id: int, k: int = TOP_K) -> list[dict]:
    cosine_sim, df = _load_model()
    user_history = _get_user_history(user_id)

    if not user_history:
        return []

    # Mapping article_id ke indeks baris di df dan vice versa
    id_to_idx = {row["article_id"]: idx for idx, row in df.iterrows()}

    candidate_scores: dict[int, float] = {}
    ref_article = None

    for art_id in user_history:
        if art_id not in id_to_idx:
            continue
        idx = id_to_idx[art_id]
        scores = list(enumerate(cosine_sim[idx]))

        for j, score in scores:
            cand_id = df.iloc[j]["article_id"]
            if cand_id in user_history:
                continue
            if cand_id not in candidate_scores or score > candidate_scores[cand_id]:
                candidate_scores[cand_id] = float(score)

        if ref_article is None:
            ref_article = art_id

    if not candidate_scores:
        return []

    top_k = sorted(candidate_scores.items(), key=lambda x: x[1], reverse=True)[:k]

    result = []
    for rank, (cand_id, _score) in enumerate(top_k, start=1):
        result.append({
            "article_id":         int(ref_article),
            "similar_article_id": int(cand_id),
            "rank_position":      rank,
        })
    return result


def save_cbf_recs(user_id: int, recs: list[dict]) -> None:
    if not recs:
        print(f"[CBF] User {user_id} tidak memiliki histori cukup untuk CBF.")
        return

    client = supabase_client()

    all_ref_ids = list({r["article_id"] for r in recs})
    for ref_id in all_ref_ids:
        client.table("article_similarity").delete().eq("article_id", ref_id).execute()

    rows = []
    for rec in recs:
        rows.append({
            "article_id":         rec["article_id"],
            "similar_article_id": rec["similar_article_id"],
            "rank_position":      rec["rank_position"],
            "generated_at":       datetime.utcnow().isoformat(),
        })

    client.table("article_similarity").insert(rows).execute()
    print(f"[CBF] User {user_id} → {len(rows)} artikel tersimpan ke article_similarity.")


def generate_article_similarity(article_id: int, k: int = TOP_K) -> list[dict]:
    """Generate top-k artikel serupa untuk SATU artikel (item-to-item)."""
    cosine_sim, df = _load_model()
    id_to_idx = {row["article_id"]: idx for idx, row in df.iterrows()}

    if article_id not in id_to_idx:
        return []

    idx = id_to_idx[article_id]
    scores = list(enumerate(cosine_sim[idx]))
    scores = [(j, s) for j, s in scores if df.iloc[j]["article_id"] != article_id]
    scores.sort(key=lambda x: x[1], reverse=True)

    result = []
    for rank, (j, _score) in enumerate(scores[:k], start=1):
        result.append({
            "article_id":         int(article_id),
            "similar_article_id": int(df.iloc[j]["article_id"]),
            "rank_position":      rank,
        })
    return result


def generate_all_article_similarities(k: int = TOP_K) -> None:
    """Generate CBF similarity untuk SEMUA artikel dan simpan ke Supabase."""
    cosine_sim, df = _load_model()
    client = supabase_client()
    all_articles = df["article_id"].tolist()

    print(f"[CBF] Generating similarity untuk {len(all_articles)} artikel...")

    # Clear existing
    client.table("article_similarity").delete().neq("article_id", -1).execute()

    rows = []
    for art_id in all_articles:
        sims = generate_article_similarity(art_id, k=k)
        rows.extend({
            "article_id":         s["article_id"],
            "similar_article_id": s["similar_article_id"],
            "rank_position":      s["rank_position"],
            "generated_at":       datetime.utcnow().isoformat(),
        } for s in sims)

    # Batch insert
    batch_size = 500
    for i in range(0, len(rows), batch_size):
        client.table("article_similarity").insert(rows[i:i+batch_size]).execute()

    print(f"[CBF] {len(rows)} rows tersimpan ke article_similarity.")


def run(user_id: int) -> None:
    recs = generate_cbf_recs(user_id)
    save_cbf_recs(user_id, recs)


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument("--train",       action="store_true")
    parser.add_argument("--user_id",     type=int, default=None)
    parser.add_argument("--all-articles", action="store_true", help="Generate similarity untuk semua artikel")
    args = parser.parse_args()

    if args.train:
        train()
    elif args.all_articles:
        generate_all_article_similarities()
    elif args.user_id:
        run(args.user_id)
    else:
        print("Gunakan --train, --all-articles, atau --user_id=<id>")
