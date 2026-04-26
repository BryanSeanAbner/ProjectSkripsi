"""
Script: update_passwords.py
Memperbarui password semua user di Supabase agar password = str(user_id).
Contoh: User ID 1 -> password '1', User ID 2 -> password '2', dst.

Karena password disimpan sebagai plain text, AuthController sudah
di-konfigurasi untuk menerima pengecekan plain text maupun bcrypt.

Jalankan: python python_engine/update_passwords.py
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from db_client import supabase_client
from config import INTERACTIONS_CSV
import pandas as pd

def update_passwords():
    client = supabase_client()

    print("[UpdatePassword] Membaca daftar user dari CSV...")
    df = pd.read_csv(INTERACTIONS_CSV)
    unique_user_ids = sorted(df['user_id'].dropna().unique().tolist())
    total = len(unique_user_ids)
    print(f"[UpdatePassword] Ditemukan {total:,} user unik. Mulai update...")

    batch_size = 200
    updated = 0
    errors  = 0

    for i in range(0, total, batch_size):
        batch = unique_user_ids[i:i + batch_size]
        for uid in batch:
            uid_int = int(uid)
            try:
                client.table("users") \
                    .update({"password": str(uid_int)}) \
                    .eq("user_id", uid_int) \
                    .execute()
                updated += 1
            except Exception as e:
                print(f"  [X] Error update user {uid_int}: {e}")
                errors += 1

        pct = min(100, int((i + len(batch)) / total * 100))
        print(f"  -> Progress: {i + len(batch):,}/{total:,} ({pct}%) | OK: {updated} | Err: {errors}")

    print(f"\n[UpdatePassword] Selesai! {updated:,} user berhasil diupdate. {errors} error.")
    print("[UpdatePassword] Sekarang setiap user dapat login dengan:")
    print("  Email    : [user_id]@gmail.com")
    print("  Password : [user_id]")
    print("  Contoh   : Email=2@gmail.com, Password=2")


if __name__ == "__main__":
    update_passwords()
