"""
Reset & Retrain — Bersihkan data Supabase lalu jalankan ulang semuanya.
========================================================================
Karena project menggunakan Supabase (cloud DB) dan koneksi PDO Laravel
mengalami error IPv6 di Laragon, semua operasi DB dilakukan via
Python + Supabase REST API (bukan php artisan migrate:fresh --seed).

Usage:
  python reset_and_retrain.py              → Reset rekomendasi + retrain + batch inference
  python reset_and_retrain.py --full       → Reset SEMUA + seed ulang + retrain + batch inference
  python reset_and_retrain.py --clean-only → Hanya bersihkan tabel, tanpa retrain
"""

import sys
import os
import time
import argparse

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from db_client import supabase_client


def clean_recommendation_tables():
    """Bersihkan 3 tabel hasil rekomendasi."""
    client = supabase_client()
    tables = {
        "top_trending":          "user_id",
        "recommendation_history": "user_id",
        "article_similarity":    "article_id",
    }
    print("\n>>> [CLEAN] Membersihkan tabel rekomendasi...")
    for table, pk in tables.items():
        try:
            client.table(table).delete().neq(pk, -1).execute()
            print(f"    [OK] {table}")
        except Exception as e:
            print(f"    [X] {table} — {e}")


def clean_all_tables():
    """Bersihkan SEMUA tabel (rekomendasi + data utama)."""
    clean_recommendation_tables()

    client = supabase_client()
    print("\n>>> [CLEAN] Membersihkan tabel data utama...")
    main_tables = [
        ("user_bookmarks",  "user_id"),
        ("user_interaction", "interaction_id"),
        ("article",         "article_id"),
        ("section",         "section_id"),
        ("users",           "user_id"),
    ]
    for table, pk in main_tables:
        try:
            client.table(table).delete().neq(pk, -1).execute()
            print(f"    [OK] {table}")
        except Exception as e:
            print(f"    [X] {table} — {e}")


def seed_data():
    """Seed ulang users, articles, dan interactions dari CSV ke Supabase."""
    print("\n>>> [SEED] Memasukkan data ke Supabase dari CSV...")

    print("\n    --- Seed Users ---")
    import seed_users
    seed_users.seed_users()

    print("\n    --- Seed Articles & Interactions ---")
    import seed_articles_interactions
    seed_articles_interactions.seed_all()


def retrain_all():
    """Training semua model + generate rekomendasi user ID 1-20."""
    print("\n>>> [TRAIN] Training semua model + generate recs user 1-20...")
    sys.argv = ["train_all.py"]
    import train_all
    train_all.main()


def main():
    parser = argparse.ArgumentParser(
        description="Reset & Retrain Pipeline",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Contoh penggunaan:
  python reset_and_retrain.py              # Reset rekomendasi + retrain
  python reset_and_retrain.py --full       # Reset SEMUA + seed + retrain
  python reset_and_retrain.py --clean-only # Hanya bersihkan tabel
        """
    )
    parser.add_argument("--full", action="store_true",
                        help="Reset SEMUA tabel + seed ulang + retrain")
    parser.add_argument("--clean-only", action="store_true",
                        help="Hanya bersihkan tabel, tanpa retrain")
    args = parser.parse_args()

    print("=" * 60)
    print("  RESET & RETRAIN PIPELINE")
    print("=" * 60)

    t0 = time.time()

    if args.full:
        print("\n[MODE] FULL RESET")
        print("  1. Hapus semua data di Supabase")
        print("  2. Seed ulang dari CSV")
        print("  3. Training semua model")
        print("  4. Batch inference semua user")
        clean_all_tables()
        seed_data()
        retrain_all()

    elif args.clean_only:
        print("\n[MODE] CLEAN ONLY")
        clean_recommendation_tables()

    else:
        print("\n[MODE] DEFAULT — Reset rekomendasi + retrain")
        clean_recommendation_tables()
        retrain_all()

    elapsed = time.time() - t0
    m, s = divmod(int(elapsed), 60)
    print(f"\n{'='*60}")
    print(f"  SELESAI dalam {m}m {s}s")
    print(f"{'='*60}\n")


if __name__ == "__main__":
    main()
