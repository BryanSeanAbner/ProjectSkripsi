"""
Popularity-Based Filtering Model
─────────────────────────────────
Mengambil artikel dengan interaksi terbanyak dari train_acu_customized5.csv,
lalu menyimpan Top-K ke tabel `top_trending` di Supabase
untuk user yang diberikan.
"""

import sys
import os
import pandas as pd
from datetime import datetime

# Pastikan folder python_engine ada di sys.path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from config import ARTICLE_CSV, TRAIN_CSV, TOP_K
from db_client import supabase_client


def get_popular_articles() -> pd.DataFrame:
    """Hitung popularitas artikel murni dari TRAIN_CSV dan kembalikan dataframe."""
    train_df    = pd.read_csv(TRAIN_CSV)
    df_articles = pd.read_csv(ARTICLE_CSV)

    popularity = (
        train_df
        .groupby("article_id")
        .size()
        .reset_index(name="interaction_count")
        .sort_values("interaction_count", ascending=False)
    )

    popularity = popularity.merge(
        df_articles[["article_id", "title", "view_count"]],
        on="article_id",
        how="left"
    )
    return popularity


def get_user_seen(user_id: int) -> set:
    """Ambil list item yang sudah dilihat user dari TRAIN_CSV"""
    train_df = pd.read_csv(TRAIN_CSV)
    user_seen_train = (
        train_df
        .groupby("user_id")["article_id"]
        .apply(set)
        .to_dict()
    )
    return user_seen_train.get(user_id, set())


def save_top_trending(user_id: int, popularity_df: pd.DataFrame, seen_arts: set, k: int = TOP_K) -> None:
    """
    Hapus hasil trending lama untuk user ini,
    lalu insert Top-K hasil popularity ke tabel top_trending.
    """
    # Filter out seen articles
    popular_articles = popularity_df.to_dict('records')
    recs = [a for a in popular_articles if a['article_id'] not in seen_arts]
    top_articles = recs[:k]

    client = supabase_client()
    client.table("top_trending").delete().eq("user_id", user_id).execute()

    rows = []
    for rank, row_dict in enumerate(top_articles, start=1):
        rows.append({
            "user_id":      int(user_id),
            "article_id":   int(row_dict["article_id"]),
            "rank_position": rank,
            "view_count":   int(row_dict.get("view_count", row_dict["interaction_count"])),
            "generated_at": datetime.utcnow().isoformat(),
        })

    client.table("top_trending").insert(rows).execute()
    print(f"[Popularity] User {user_id} -> {len(rows)} artikel tersimpan ke top_trending.")


def run(user_id: int) -> None:
    """Entry point: generate popularity recs untuk satu user."""
    pop_df    = get_popular_articles()
    seen_arts = get_user_seen(user_id)
    save_top_trending(user_id, pop_df, seen_arts, TOP_K)


def train() -> None:
    """Evaluasi Popularity Model seperti training model lainnya."""
    import numpy as np
    from collections import defaultdict
    from config import TEST_CSV
    
    print("[Popularity] Memuat dataset (Train & Test)...")
    train_df    = pd.read_csv(TRAIN_CSV)
    test_df     = pd.read_csv(TEST_CSV)
    df_articles = pd.read_csv(ARTICLE_CSV)

    print(f"[Popularity] Total interaksi train : {len(train_df):,}")
    print(f"[Popularity] Total interaksi test  : {len(test_df):,}")
    print(f"[Popularity] Total user train      : {train_df['user_id'].nunique():,}")
    print(f"[Popularity] Total user test       : {test_df['user_id'].nunique():,}")

    popularity = (
        train_df
        .groupby("article_id")
        .size()
        .reset_index(name="interaction_count")
        .sort_values("interaction_count", ascending=False)
    )

    popularity = popularity.merge(
        df_articles[["article_id", "title", "view_count"]],
        on="article_id",
        how="left"
    )

    popular_articles = popularity["article_id"].tolist()

    user_seen_train = (
        train_df
        .groupby("user_id")["article_id"]
        .apply(set)
        .to_dict()
    )

    ground_truth = defaultdict(set)
    for _, row in test_df.iterrows():
        ground_truth[row["user_id"]].add(row["article_id"])

    def recommend_popular(user_id, top_k=10):
        seen = user_seen_train.get(user_id, set())
        recs = [a for a in popular_articles if a not in seen]
        return recs[:top_k]

    def ndcg_at_k(recommended, relevant_set, k):
        rec_k = recommended[:k]
        dcg   = sum(1.0 / np.log2(i + 2) for i, item in enumerate(rec_k) if item in relevant_set)
        ideal = sum(1.0 / np.log2(i + 2) for i in range(min(len(relevant_set), k)))
        return dcg / ideal if ideal > 0 else 0.0

    def evaluate(k):
        precisions, recalls, ndcgs = [], [], []
        for user_id, gt_items in ground_truth.items():
            recs = recommend_popular(user_id, top_k=k)
            hits = sum(1 for r in recs if r in gt_items)
            precisions.append(hits / k)
            recalls.append(hits / len(gt_items) if gt_items else 0.0)
            ndcgs.append(ndcg_at_k(recs, gt_items, k))
        return {
            "Precision": float(np.mean(precisions)),
            "Recall":    float(np.mean(recalls)),
            "NDCG":      float(np.mean(ndcgs)),
        }

    print("\n─── Test Results (Popularity Based) ───")
    for k in [5, 10, 20]:
        m = evaluate(k)
        print(f"  K={k:2d} | Precision={m['Precision']:.4f} | Recall={m['Recall']:.4f} | NDCG={m['NDCG']:.4f}")
        
    print("\n[Popularity] Evaluasi selesai.")


if __name__ == "__main__":
    import argparse
    parser = argparse.ArgumentParser()
    parser.add_argument("--train", action="store_true")
    parser.add_argument("--user_id", type=int, default=None)
    args = parser.parse_args()

    if args.train:
        train()
    elif args.user_id:
        run(args.user_id)
    else:
        print("Gunakan --train atau --user_id=<id>")
