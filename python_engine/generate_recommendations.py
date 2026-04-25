"""
Master script: Generate rekomendasi untuk satu user dari semua model.
Dipanggil oleh Laravel Job:
  python generate_recommendations.py --user_id=<id>
"""

import sys
import os
import argparse

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import train_popularity
import train_cbf
import train_lightgcn


def main():
    parser = argparse.ArgumentParser(
        description="Generate Top-5 rekomendasi untuk satu user"
    )
    parser.add_argument("--user_id", type=int, required=True,
                        help="ID user yang akan di-generate rekomendasinya")
    args = parser.parse_args()

    user_id = args.user_id
    print(f"\n{'='*50}")
    print(f" Generating recommendations for user_id = {user_id}")
    print(f"{'='*50}\n")

    # 1. Popularity
    print(">>> [1/3] Popularity-Based Filtering...")
    try:
        train_popularity.run(user_id)
    except Exception as e:
        print(f"    ERROR Popularity: {e}")

    # 2. CBF
    print("\n>>> [2/3] Content-Based Filtering...")
    try:
        train_cbf.run(user_id)
    except Exception as e:
        print(f"    ERROR CBF: {e}")

    # 3. LightGCN
    print("\n>>> [3/3] LightGCN...")
    try:
        train_lightgcn.run(user_id)
    except Exception as e:
        print(f"    ERROR LightGCN: {e}")

    print(f"\n{'='*50}")
    print(f" Selesai! User {user_id} recommendations generated.")
    print(f"{'='*50}\n")


if __name__ == "__main__":
    main()
