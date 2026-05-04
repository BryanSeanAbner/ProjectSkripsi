import os
import sys
import pandas as pd
import numpy as np
import torch
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import INTERACTIONS_CSV, ARTICLE_CSV, TOP_K
from db_client import supabase_client
import train_popularity
import train_lightgcn
import train_cbf

def main():
    client = supabase_client()
    batch_size = 500

    print("\n=========================================")
    print("  MEMULAI BATCH GENERATION (ALL USERS)")
    print("=========================================\n")

    # Load shared data
    interactions_df = pd.read_csv(INTERACTIONS_CSV)
    unique_users = interactions_df['user_id'].unique()
    n_users = len(unique_users)

    # ---------------------------------------------------------
    # 1. POPULARITY BASED FILTERING -> tabel top_trending
    # ---------------------------------------------------------
    print(">>> [1/3] Menghitung Popularity untuk semua user...")
    pop_df = train_popularity.get_popular_articles()
    # Pre-cache user history
    user_seen_dict = (
        interactions_df.groupby("user_id")["article_id"]
        .apply(set).to_dict()
    )

    popular_articles = pop_df.to_dict('records')
    pop_rows = []

    for uid in unique_users:
        seen = user_seen_dict.get(uid, set())
        recs = [a for a in popular_articles if a['article_id'] not in seen]
        top_k = recs[:TOP_K]
        for rank, row_dict in enumerate(top_k, start=1):
            pop_rows.append({
                "user_id": int(uid),
                "article_id": int(row_dict["article_id"]),
                "rank_position": rank,
                "view_count": int(row_dict.get("view_count", row_dict["interaction_count"])),
                "generated_at": datetime.utcnow().isoformat()
            })

    print(f"    - Terkumpul {len(pop_rows)} baris. Menyisipkan ke Supabase 'top_trending'...")
    client.table("top_trending").delete().neq("user_id", -1).execute() # Delete all
    for i in range(0, len(pop_rows), batch_size):
        try:
            client.table("top_trending").insert(pop_rows[i:i+batch_size]).execute()
        except:
            pass
    print("    [OK] Popularity selesai.")

    # ---------------------------------------------------------
    # 2. LIGHTGCN -> tabel recommendation_history
    # ---------------------------------------------------------
    print("\n>>> [2/3] Menghitung LightGCN untuk semua user...")
    try:
        model, meta = train_lightgcn._load_model()
        user_enc = meta["user_enc"]
        item_enc = meta["item_enc"]
        pop_score = meta["pop_score"].to(train_lightgcn.device)
        train_gt = meta["train_gt"]
        val_gt = meta["val_gt"]
        
        with torch.no_grad():
            u_emb, i_emb = model()
            
        lgcn_rows = []
        for uid in unique_users:
            # Map ke internal ID
            if uid not in user_enc.classes_: continue
            u_idx = int(user_enc.transform([uid])[0])
            
            # (n_items, )
            scores = (i_emb @ u_emb[u_idx]).clone()
            scores = scores - train_lightgcn.ALPHA * pop_score
            
            seen = train_gt.get(u_idx, set()) | val_gt.get(u_idx, set())
            if seen:
                scores[list(seen)] = float("-inf")
            
            top_k_idx = torch.topk(scores, TOP_K).indices.cpu().numpy()
            top_k_arts = item_enc.inverse_transform(top_k_idx)
            
            for rank, art_id in enumerate(top_k_arts, start=1):
                lgcn_rows.append({
                    "user_id": int(uid),
                    "article_id": int(art_id),
                    "rank_position": rank,
                    "generated_at": datetime.utcnow().isoformat()
                })

        print(f"    - Terkumpul {len(lgcn_rows)} baris. Menyisipkan ke Supabase 'recommendation_history'...")
        client.table("recommendation_history").delete().neq("user_id", -1).execute()
        for i in range(0, len(lgcn_rows), batch_size):
            try:
                client.table("recommendation_history").insert(lgcn_rows[i:i+batch_size]).execute()
            except:
                pass
        print("    [OK] LightGCN selesai.")

    except Exception as e:
        print(f"    [X] Error LightGCN: {e}")

    # ---------------------------------------------------------
    # 3. CONTENT-BASED FILTERING -> tabel article_similarity (Per-Article)
    # ---------------------------------------------------------
    print("\n>>> [3/3] Menghitung CBF untuk semua artikel (Item-to-Item)...")
    try:
        sim_matrix, article_df = train_cbf._load_model()
        article_ids = article_df["article_id"].tolist()
        
        cbf_rows = []
        # Untuk SETIAP artikel, cari Top-5 mirip
        for i, ref_id in enumerate(article_ids):
            scores = list(enumerate(sim_matrix[i]))
            # Urutkan berdasarkan similarity tertinggi
            scores = sorted(scores, key=lambda x: x[1], reverse=True)
            
            rank = 1
            for j, score in scores:
                cand_id = article_ids[j]
                if cand_id == ref_id:
                    continue # Jangan rekomendasikan artikel itu sendiri
                
                cbf_rows.append({
                    "article_id": int(ref_id),
                    "similar_article_id": int(cand_id),
                    "rank_position": rank,
                    "generated_at": datetime.utcnow().isoformat()
                })
                rank += 1
                if rank > TOP_K:
                    break
        
        print(f"    - Terkumpul {len(cbf_rows)} baris. Menyisipkan ke Supabase 'article_similarity'...")
        client.table("article_similarity").delete().neq("article_id", -1).execute()
        for i in range(0, len(cbf_rows), batch_size):
            try:
                client.table("article_similarity").insert(cbf_rows[i:i+batch_size]).execute()
            except:
                pass
        print("    [OK] CBF selesai.")

    except Exception as e:
        print(f"    [X] Error CBF: {e}")

if __name__ == "__main__":
    main()
