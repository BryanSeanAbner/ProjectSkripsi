import os
import sys
import pandas as pd
from datetime import datetime
import numpy as np

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from db_client import supabase_client
from config import BASE_DIR, INTERACTIONS_CSV, ARTICLE_CSV

def seed_all():
    client = supabase_client()
    batch_size = 500

    print("\n[Seeder] --- MEMUAT ARTIKEL & SECTION ---")
    df_art = pd.read_csv(ARTICLE_CSV)
    
    # 1. Seed Section
    # Ambil unique section_id
    sections = df_art['section_id'].dropna().unique()
    print(f"Ditemukan {len(sections)} sections unik. Menyisipkan ke tabel 'section'...")
    
    sec_rows = []
    for sid in sections:
        sec_rows.append({
            "section_id": str(sid)
        })
    
    # Upsert sections
    if sec_rows:
        try:
            client.table("section").upsert(sec_rows).execute()
            print(f" ✓ {len(sec_rows)} sections berhasil disisipkan.")
        except Exception as e:
            print(f" [X] Error menyisipkan section: {e}")

    # 2. Seed Articles
    print(f"\nDitemukan {len(df_art)} artikel. Menyisipkan ke tabel 'article'...")
    # Clean up NaN
    df_art = df_art.replace({np.nan: None})
    
    art_rows = []
    art_inserted = 0
    for idx, row in df_art.iterrows():
        art_rows.append({
            "article_id": int(row["article_id"]),
            "title": str(row["title"]),
            "content": row["content"] if row["content"] else "",
            "photo_url": row["photo_url"] if row["photo_url"] else "",
            "publish_date": row["publish_date"] if row["publish_date"] else datetime.utcnow().isoformat(),
            "url": row["url"] if row["url"] else "",
            "section_id": str(row["section_id"]) if row["section_id"] else None,
            "view_count": int(row["view_count"]) if row["view_count"] is not None else 0
        })
        
        if len(art_rows) >= batch_size:
            try:
                client.table("article").upsert(art_rows).execute()
                art_inserted += len(art_rows)
                print(f"  → {art_inserted} artikel tersimpan...")
            except Exception as e:
                print(f"  [X] Error pada batch artikel: {e}")
            art_rows = []
            
    if art_rows:
        try:
            client.table("article").upsert(art_rows).execute()
            art_inserted += len(art_rows)
            print(f"  → {art_inserted} artikel tersimpan...")
        except Exception as e:
            print(f"  [X] Error pada sisa artikel: {e}")

    print(f" ✓ {art_inserted} total artikel berhasil disisipkan.")

    # 3. Seed User Interactions
    print("\n[Seeder] --- MEMUAT USER INTERACTIONS ---")
    df_int = pd.read_csv(INTERACTIONS_CSV)
    print(f"Ditemukan {len(df_int)} interaksi. Menyisipkan ke tabel 'user_interaction'...")
    
    int_rows = []
    int_inserted = 0
    for idx, row in df_int.iterrows():
        int_rows.append({
            "interaction_id": int(row["interaction_id"]),
            "user_id": int(row["user_id"]),
            "article_id": int(row["article_id"])
        })
        
        if len(int_rows) >= batch_size:
            try:
                client.table("user_interaction").upsert(int_rows).execute()
                int_inserted += len(int_rows)
                print(f"  → {int_inserted} interaksi tersimpan...")
            except Exception as e:
                print(f"  [X] Error pada batch interaksi: {e}")
            int_rows = []
            
    if int_rows:
        try:
            client.table("user_interaction").upsert(int_rows).execute()
            int_inserted += len(int_rows)
            print(f"  → {int_inserted} interaksi tersimpan...")
        except Exception as e:
            print(f"  [X] Error pada sisa interaksi: {e}")

    print(f" ✓ {int_inserted} total interaksi berhasil disisipkan.")

if __name__ == "__main__":
    seed_all()
