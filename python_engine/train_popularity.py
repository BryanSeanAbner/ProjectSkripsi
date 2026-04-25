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
    print(f"[Popularity] User {user_id} → {len(rows)} artikel tersimpan ke top_trending.")


def run(user_id: int) -> None:
    """Entry point: generate popularity recs untuk satu user."""
    pop_df    = get_popular_articles()
    seen_arts = get_user_seen(user_id)
    save_top_trending(user_id, pop_df, seen_arts, TOP_K)


if __name__ == "__main__":
    uid = int(sys.argv[1]) if len(sys.argv) > 1 else 18
    run(uid)

