"""
Master training script — jalankan sekali untuk melatih semua model.
Dipanggil via: php artisan models:train
Atau langsung: python train_all.py
"""

import sys
import os
import time
import argparse

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import train_cbf
import train_lightgcn

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

    # 2. LightGCN
    print(">>> [2/2] LightGCN...")
    t0 = time.time()
    try:
        train_lightgcn.train(epochs=args.epochs, embedding_dim=args.embedding_dim, num_layers=args.num_layers)
        print(f"    [OK] LightGCN selesai dalam {time.time()-t0:.1f}s")
    except Exception as e:
        print(f"    [X] ERROR: {e}")

    print("\n" + "="*60)
    print("  TRAINING COMPLETE — Model weights tersimpan di saved_models/")
    print("="*60 + "\n")

if __name__ == "__main__":
    main()
