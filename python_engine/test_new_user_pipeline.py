"""
Test Pipeline — Tambah User Baru & Retrain Model
=================================================
Usage: python test_new_user_pipeline.py --user_id <id>

Alur:
  1. Fetch interaksi user baru dari Supabase (user_interaction)
  2. Gabung dengan CSV interaksi terbaru
  3. Train-test split (logika dari user)
  4. Simpan 3 file CSV berversi (N)
  5. Set env var lalu retrain LightGCN dari epoch 0
  6. Generate & simpan rekomendasi user baru ke Supabase
"""

import os
import sys
import argparse
import numpy as np
import pandas as pd
from datetime import datetime
from collections import defaultdict

# ─── Env vars HARUS di-set SEBELUM import apapun dari python_engine ───────────
# Akan di-update setelah kita tau path CSV baru

ENGINE_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, ENGINE_DIR)

# Import config & client TANPA train modules dulu
from config import ARTICLE_CSV, BASE_DIR, SAVED_MODELS_DIR, TOP_K
from db_client import supabase_client

# ─── Helpers ──────────────────────────────────────────────────────────────────

def get_next_version_paths():
    """Cari versi (N) berikutnya untuk 4 file CSV output."""
    base_dir = BASE_DIR
    n = 1
    while True:
        inter = os.path.join(base_dir, f"acu_interactions_customized5({n}).csv")
        if not os.path.exists(inter):
            train   = os.path.join(base_dir, f"train_acu_customized5({n}).csv")
            test    = os.path.join(base_dir, f"test_acu_customized5({n}).csv")
            article = os.path.join(base_dir, f"article_dataset5({n}).csv")
            return inter, train, test, article, n
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


def split_clean(df, vc_lookup, test_frac=0.2, seed=42):
    """Train-test split persis logika dari user."""
    rng_split = np.random.default_rng(seed)

    df["_vc"]     = df["article_id"].map(vc_lookup).fillna(1)
    df["_weight"] = 1.0 / (df["_vc"] ** 2)

    train_idx = set()
    test_idx  = set()

    for uid, rows in df.groupby("user_id"):
        idx    = rows.index.values
        n      = len(idx)
        n_test  = max(1, int(n * test_frac))
        n_train = n - n_test
        if n_train == 0:
            n_train, n_test = 1, n - 1

        weights = rows["_weight"].values
        probs   = weights / weights.sum()
        test_s  = rng_split.choice(idx, size=n_test, replace=False, p=probs)
        train_s = list(set(idx) - set(test_s))
        train_idx.update(train_s)
        test_idx.update(test_s)

    # Pastikan setiap artikel ada di kedua split
    for art_id, rows in df.groupby("article_id"):
        idx = set(rows.index)
        if not (idx & train_idx):
            m = list(idx & test_idx)[0]
            test_idx.discard(m); train_idx.add(m)
        if not (idx & test_idx):
            m = list(idx & train_idx)[0]
            train_idx.discard(m); test_idx.add(m)

    df_train = df.loc[sorted(train_idx)].reset_index(drop=True)
    df_test  = df.loc[sorted(test_idx)].reset_index(drop=True)

    for d in [df_train, df_test]:
        d.drop(columns=["_vc", "_weight"], inplace=True, errors="ignore")

    return df_train, df_test


# ─── Main Pipeline ─────────────────────────────────────────────────────────────

def run_pipeline(user_id: int):
    client = supabase_client()

    print(f"\n{'='*60}")
    print(f"  TEST PIPELINE — User Baru ID: {user_id}")
    print(f"{'='*60}\n")

    # ── 1. Fetch interaksi dari Supabase ──────────────────────────────────────
    print(">>> [1/6] Mengambil interaksi user baru dari Supabase...")
    resp = client.table("user_interaction").select("user_id,article_id").eq("user_id", user_id).execute()
    if not resp.data:
        print(f"    [X] Tidak ada interaksi untuk user {user_id}!"); sys.exit(1)

    new_df = pd.DataFrame(resp.data)[["user_id", "article_id"]].drop_duplicates()
    new_df["user_id"]    = new_df["user_id"].astype(int)
    new_df["article_id"] = new_df["article_id"].astype(int)
    print(f"    [OK] {len(new_df)} interaksi ditemukan.")

    # ── 2. Load interaksi terbaru ─────────────────────────────────────────────
    print(">>> [2/6] Memuat dataset interaksi terbaru...")
    latest_csv = get_latest_interactions_csv()
    print(f"    Base file: {os.path.basename(latest_csv)}")
    existing_df = pd.read_csv(latest_csv)[["user_id", "article_id"]]

    combined = pd.concat([existing_df, new_df], ignore_index=True).drop_duplicates(["user_id", "article_id"])
    print(f"    [OK] Gabungan: {len(combined):,} baris, {combined['user_id'].nunique():,} user")

    # ── 3. Load artikel, update view_count, & simpan versi artikel baru ─────────
    print(">>> [3/6] Memuat & memperbarui data artikel (view_count)...")
    latest_article_csv = get_latest_article_csv()
    print(f"    Base file: {os.path.basename(latest_article_csv)}")
    art_df = pd.read_csv(latest_article_csv)

    # Increment view_count untuk setiap artikel yang diklik user baru
    clicked_ids = set(new_df["article_id"].unique())
    
    for aid in clicked_ids:
        # Cari baris artikel ini
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

    art_df   = art_df[art_df["view_count"] >= 5].reset_index(drop=True)
    vc_lookup = dict(zip(art_df["article_id"].astype(int), art_df["view_count"].astype(int)))

    combined = combined[combined["article_id"].isin(vc_lookup)].reset_index(drop=True)

    # Filter cold-start (>= 2 interaksi per user)
    uc = combined["user_id"].value_counts()
    combined = combined[combined["user_id"].isin(uc[uc >= 2].index)].reset_index(drop=True)
    print(f"    [OK] Setelah filter: {len(combined):,} baris, {combined['user_id'].nunique():,} user")

    # ── 4. Train-test split ───────────────────────────────────────────────────
    print(">>> [4/6] Menjalankan train-test split...")
    df_train, df_test = split_clean(combined.copy(), vc_lookup)
    print(f"    [OK] Train: {len(df_train):,} | Test: {len(df_test):,}")

    # ── 5. Simpan CSV berversi (interactions + train + test + article) ───────────
    print(">>> [5/6] Menyimpan file CSV berversi...")
    new_inter_path, new_train_path, new_test_path, new_article_path, ver = get_next_version_paths()

    def add_id(df):
        d = df.copy()
        d.insert(0, "interaction_id", np.arange(1, len(d) + 1))
        return d

    add_id(combined).to_csv(new_inter_path, index=False)
    add_id(df_train).to_csv(new_train_path, index=False)
    add_id(df_test).to_csv(new_test_path,  index=False)
    art_df.to_csv(new_article_path, index=False)  # Simpan artikel dengan view_count terbaru
    print(f"    [OK] Versi ({ver}) tersimpan:")
    print(f"         {os.path.basename(new_inter_path)}")
    print(f"         {os.path.basename(new_train_path)}")
    print(f"         {os.path.basename(new_test_path)}")
    print(f"         {os.path.basename(new_article_path)}  <- view_count diperbarui")

    # ── 6. Retrain LightGCN + Popularity ────────────────────────────────────
    print(">>> [6/6] Melatih ulang model dengan data baru...")

    # Set env vars agar config.py membaca path baru saat diimport ulang
    os.environ['OVERRIDE_TRAIN_CSV']   = new_train_path
    os.environ['OVERRIDE_TEST_CSV']    = new_test_path
    os.environ['OVERRIDE_ARTICLE_CSV'] = new_article_path  # Artikel terbaru dengan view_count baru

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
