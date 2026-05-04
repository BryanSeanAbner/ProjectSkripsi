import os
import sys
import pandas as pd
from datetime import datetime

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from config import INTERACTIONS_CSV
from db_client import supabase_client

def seed_users():
    print("[Seeder] Memuat data interaksi...")
    df = pd.read_csv(INTERACTIONS_CSV)
    
    unique_users = df['user_id'].unique()
    print(f"[Seeder] Ditemukan {len(unique_users)} users unik. Mulai seeding...")

    client = supabase_client()
    
    # Supabase REST API limit is typically 1000 rows per insert, but let's do 200 for safety
    batch_size = 200
    rows = []
    
    # Password disimpan plain text. AuthController sudah mendukung
    # pencocokan plain text ($storedPassword === $inputPassword).
    default_password = "1"
    
    import random
    genders = ['M', 'F']
    
    total_inserted = 0
    
    for uid in unique_users:
        uid_int = int(uid)
        rows.append({
            "user_id": uid_int,
            "username": str(uid_int),
            "email": f"{uid_int}@gmail.com",
            "password": default_password,
            "gender": random.choice(genders),
            "age": random.randint(18, 60)
        })
        
        if len(rows) >= batch_size:
            # upsert based on user_id
            client.table("users").upsert(rows).execute()
            total_inserted += len(rows)
            print(f"  → {total_inserted} users tersimpan...")
            rows = []
            
    if rows:
        client.table("users").upsert(rows).execute()
        total_inserted += len(rows)
        print(f"  → {total_inserted} users tersimpan...")

    print(f"[Seeder] Selesai! Sebanyak {total_inserted} user berhasil ditambahkan/diperbarui.")

if __name__ == "__main__":
    seed_users()
