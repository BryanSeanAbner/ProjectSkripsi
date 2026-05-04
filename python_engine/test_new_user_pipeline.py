"""
Test Pipeline — Tambah User Baru & Retrain Model
=================================================
Usage: python test_new_user_pipeline.py --user_id <id>

Alur:
  1. Fetch interaksi user baru dari Supabase (user_interaction)
  2. Gabung dengan CSV interaksi terbaru
  3. Update view_count artikel (CSV + Supabase)
  4. Simpan 2 file CSV berversi (interactions + article)
  5. Retrain LightGCN dari epoch 0
  6. Generate & simpan rekomendasi user baru ke Supabase
"""

import os
import sys
import argparse
import numpy as np
import pandas as pd
from datetime import datetime

# ─── Env vars HARUS di-set SEBELUM import apapun dari python_engine ───────────
ENGINE_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, ENGINE_DIR)

# Import config & client TANPA train modules dulu
from config import ARTICLE_CSV, BASE_DIR, SAVED_MODELS_DIR, TOP_K
from db_client import supabase_client

# ─── Helpers ──────────────────────────────────────────────────────────────────

def get_next_version_paths():
    """Cari versi (N) berikutnya untuk 2 file CSV output."""
    base_dir = BASE_DIR
    n = 1
    while True:
        inter = os.path.join(base_dir, f"acu_interactions_customized5({n}).csv")
        if not os.path.exists(inter):
            article = os.path.join(base_dir, f"article_dataset5({n}).csv")
            return inter, article, n
        n += 1


def get_latest_article_csv():
    """Ambil file artikel versi terbaru (atau base jika belum ada versi)."""
    base = os.path.join(BASE_DIR, "article_dataset5.csv")
    n = 1
    last = base
    while True:
        versioned = os.path.join(BASE_DIR, f"article_dataset5({n}).csv")
        if os.path.exists(versioned):
            last = versioned
            n += 1
        else:
            break
    return last


def get_latest_interactions_csv():
    """Ambil file interaksi versi terbaru (atau base jika belum ada versi)."""
    base = os.path.join(BASE_DIR, "acu_interactions_customized5.csv")
    n = 1
    last = base
    while True:
        versioned = os.path.join(BASE_DIR, f"acu_interactions_customized5({n}).csv")
        if os.path.exists(versioned):
            last = versioned
            n += 1
        else:
            break
    return last


# ─── Main Pipeline ─────────────────────────────────────────────────────────────

def run_pipeline(user_id: int):
    client = supabase_client()

    print(f"\n{'='*60}")
    print(f"  TEST PIPELINE — User Baru ID: {user_id}")
    print(f"{'='*60}\n")

    # ── 1. Fetch interaksi dari Supabase ──────────────────────────────────────
    print(">>> [1/5] Mengambil interaksi user baru dari Supabase...")
    resp = client.table("user_interaction").select("user_id,article_id").eq("user_id", user_id).execute()
    if not resp.data:
        print(f"    [X] Tidak ada interaksi untuk user {user_id}!"); sys.exit(1)

    new_df = pd.DataFrame(resp.data)[["user_id", "article_id"]].drop_duplicates()
    new_df["user_id"]    = new_df["user_id"].astype(int)
    new_df["article_id"] = new_df["article_id"].astype(int)
    print(f"    [OK] {len(new_df)} interaksi ditemukan.")

    # ── 2. Load interaksi terbaru & gabungkan ─────────────────────────────────
    print(">>> [2/5] Memuat dataset interaksi terbaru...")
    latest_csv = get_latest_interactions_csv()
    print(f"    Base file: {os.path.basename(latest_csv)}")
    existing_df = pd.read_csv(latest_csv)[["user_id", "article_id"]]

    combined = pd.concat([existing_df, new_df], ignore_index=True).drop_duplicates(["user_id", "article_id"])
    print(f"    [OK] Gabungan: {len(combined):,} baris, {combined['user_id'].nunique():,} user")

    # ── 3. Load artikel, update view_count ────────────────────────────────────
    print(">>> [3/5] Memuat & memperbarui data artikel (view_count)...")
    latest_article_csv = get_latest_article_csv()
    print(f"    Base file: {os.path.basename(latest_article_csv)}")
    art_df = pd.read_csv(latest_article_csv)

    # Increment view_count untuk setiap artikel yang diklik user baru
    clicked_ids = set(new_df["article_id"].unique())

    for aid in clicked_ids:
        idx = art_df.index[art_df["article_id"] == aid]
        if not idx.empty:
            new_vc = int(art_df.loc[idx[0], "view_count"]) + 1
            art_df.loc[idx[0], "view_count"] = new_vc

            # Update ke Supabase agar website juga melihat perubahan ini
            try:
                client.table("article").update({"view_count": new_vc}).eq("article_id", int(aid)).execute()
            except Exception as e:
                print(f"    [X] Gagal update view_count Supabase untuk artikel {aid}: {e}")

    print(f"    [OK] view_count diperbarui di CSV dan Supabase untuk {len(clicked_ids)} artikel.")

    # Filter cold-start (>= 2 interaksi per user)
    uc = combined["user_id"].value_counts()
    combined = combined[combined["user_id"].isin(uc[uc >= 2].index)].reset_index(drop=True)
    print(f"    [OK] Setelah filter: {len(combined):,} baris, {combined['user_id'].nunique():,} user")

    # ── 4. Simpan CSV berversi (interactions + article) ───────────────────────
    print(">>> [4/5] Menyimpan file CSV berversi...")
    new_inter_path, new_article_path, ver = get_next_version_paths()

    def add_id(df):
        d = df.copy()
        d.insert(0, "interaction_id", np.arange(1, len(d) + 1))
        return d

    add_id(combined).to_csv(new_inter_path, index=False)
    art_df.to_csv(new_article_path, index=False)
    print(f"    [OK] Versi ({ver}) tersimpan:")
    print(f"         {os.path.basename(new_inter_path)}")
    print(f"         {os.path.basename(new_article_path)}  <- view_count diperbarui")

    # ── 5. Retrain LightGCN + Popularity ──────────────────────────────────────
    print(">>> [5/5] Melatih ulang model dengan data baru...")

    # Set env vars agar config.py membaca path baru saat diimport ulang
    os.environ['OVERRIDE_INTERACTIONS_CSV'] = new_inter_path
    os.environ['OVERRIDE_ARTICLE_CSV']      = new_article_path

    # Hapus cache module agar config.py di-import ulang dengan env baru
    for mod in ['config', 'train_lightgcn', 'train_popularity']:
        sys.modules.pop(mod, None)

    # Import ulang setelah env di-set
    import train_lightgcn
    import train_popularity

    print("    [LightGCN] Memulai training dari epoch 0...")
    train_lightgcn.train()
    print("    [LightGCN] Training selesai.")

    print("    [Popularity] Menghitung rekomendasi...")
    pop_df    = train_popularity.get_popular_articles()
    seen_arts = train_popularity.get_user_seen(user_id)
    train_popularity.save_top_trending(user_id, pop_df, seen_arts, TOP_K)

    print("    [LightGCN] Menyimpan rekomendasi user baru...")
    recs = train_lightgcn.generate_lightgcn_recs(user_id, k=TOP_K)
    if recs:
        train_lightgcn.save_lightgcn_recs(user_id, recs)
    else:
        print(f"    [!] User {user_id} tidak ada di embedding (cold-start). Melewati LightGCN recs.")

    print(f"\n{'='*60}")
    print(f"  PIPELINE SELESAI — User {user_id} siap!")
    print(f"{'='*60}\n")
    return True


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--user_id", type=int, required=True, help="ID user baru dari tabel users")
    args = parser.parse_args()
    run_pipeline(args.user_id)
