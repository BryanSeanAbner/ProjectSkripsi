"""
Master training script — jalankan sekali untuk melatih semua model.
Dipanggil via: php artisan models:train
Atau langsung: python train_all.py
"""

import sys
import os
import time
import argparse

import pandas as pd
from config import TRAIN_CSV

import train_popularity
import train_cbf
import train_lightgcn
import generate_recommendations

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--epochs", type=int, default=100)
    parser.add_argument("--embedding_dim", type=int, default=8)
    parser.add_argument("--num_layers", type=int, default=2)
    args = parser.parse_args()

    print("\n" + "="*60)
    print("  TRAINING ALL RECOMMENDATION MODELS")
    print("="*60 + "\n")

    print(f"[Config] LightGCN Epochs: {args.epochs}, Emb_Dim: {args.embedding_dim}, Layers: {args.num_layers}\n")

    # 1. CBF
    print(">>> [1/2] Content-Based Filtering (TF-IDF)...")
    t0 = time.time()
    try:
        train_cbf.train()
        print(f"    [OK] CBF selesai dalam {time.time()-t0:.1f}s")
    except Exception as e:
        print(f"    [X] ERROR: {e}")

    print()

    # 1.5 Popularity Evaluation
    print(">>> [2/3] Popularity-Based Filtering Evaluation...")
    t0 = time.time()
    try:
        train_popularity.train()
        print(f"    [OK] Popularity selesai dalam {time.time()-t0:.1f}s")
    except Exception as e:
        print(f"    [X] ERROR: {e}")

    print()

    # 3. LightGCN
    print(">>> [3/3] LightGCN...")
    t0 = time.time()
    try:
        train_lightgcn.train(epochs=args.epochs, embedding_dim=args.embedding_dim, num_layers=args.num_layers)
        print(f"    [OK] LightGCN selesai dalam {time.time()-t0:.1f}s")
    except Exception as e:
        print(f"    [X] ERROR: {e}")

    # 4. Auto-Generate Demo Users
    print("\n>>> [4/4] Auto-Generate Rekomendasi (User ID 1 sampai 20)...")
    t0 = time.time()
    try:
        # Generate secara spesifik untuk User ID 1 sampai 20
        demo_users = list(range(1, 21))
        
        print(f"    Mulai generate & simpan ke Supabase untuk {len(demo_users)} user...")
        for idx, uid in enumerate(demo_users, 1):
            print(f"    -> Progress: {idx}/{len(demo_users)} (User ID: {int(uid)})")
            generate_recommendations.generate_for_user(int(uid))
            
        print(f"    [OK] Auto-Generate Selesai dalam {time.time()-t0:.1f}s")
    except Exception as e:
        print(f"    [X] ERROR Auto-Generate: {e}")

    print("\n" + "="*60)
    print("  TRAINING & AUTO-INFERENCE COMPLETE — Model weights tersimpan di saved_models/")
    print("="*60 + "\n")

if __name__ == "__main__":
    main()
