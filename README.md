# Sistem Rekomendasi Berita Anti-FOMO

Prototipe aplikasi web berita dengan sistem rekomendasi berbasis **Laravel 13**, **Supabase**, dan **Python (LightGCN + CBF + Popularity)**.

## Persyaratan

| Software | Versi |
|----------|-------|
| PHP | 8.3+ |
| Composer | 2.x |
| Python | 3.11+ (opsional, untuk generate rekomendasi saat login) |
| Ekstensi PHP | `sqlite3`, `openssl`, `mbstring`, `curl` |

Laragon di Windows sudah memenuhi persyaratan di atas.

## Setup Cepat (Setelah Git Clone)

### Windows (PowerShell / Laragon)

```powershell
.\setup.ps1
php artisan serve
```

### Linux / macOS

```bash
chmod +x setup.sh
./setup.sh
php artisan serve
```

### Manual

```bash
composer install
php artisan project:setup
php artisan serve
```

Buka browser: **http://127.0.0.1:8000**

## Login Demo

Database Supabase **shared** sudah berisi data demo. Gunakan akun berikut:

| Email | Password | Keterangan |
|-------|----------|------------|
| `1@gmail.com` s/d `20@gmail.com` | `1` | User warm-start (sudah punya rekomendasi personal) |
| Daftar via `/register` | bebas | User cold-start (rekomendasi popularitas) |

## Arsitektur Singkat

```
Browser (Blade + Supabase JS) ──► Supabase Cloud (data utama)
Laravel (Auth, routing, session) ──► Supabase REST API
Python Engine ──► Training & generate rekomendasi ──► Supabase
SQLite lokal ──► Session, cache, queue Laravel saja
```

## File Penting

| File / Folder | Fungsi |
|---------------|--------|
| `.env.example` | Template konfigurasi (Supabase URL & key sudah terisi) |
| `python_engine/config.py` | Kredensial Supabase untuk script Python |
| `python_engine/reset_and_retrain.py` | Reset + seed + training ulang |
| `article_dataset.csv` | Dataset artikel |
| `acu_interactions.csv` | Dataset interaksi user-artikel |
| `python_engine/saved_models/` | Model ML yang sudah dilatih |

## Reset Database Supabase (Opsional)

Jika ingin mengisi ulang data dari CSV dan training model:

```bash
cd python_engine
pip install -r requirements.txt
python reset_and_retrain.py --full
```

Proses ini memakan waktu ~10–30 menit tergantung spesifikasi laptop.

## Perintah Artisan Berguna

```bash
php artisan serve              # Jalankan web server
php artisan project:setup      # Setup pertama kali
php artisan models:train       # Training ulang semua model ML
```

## Troubleshooting

**Halaman kosong / error Supabase**
- Pastikan `.env` ada dan berisi `SUPABASE_URL` + `SUPABASE_SERVICE_KEY`
- Jalankan `php artisan config:clear`

**Login gagal "Gagal menghubungi server database"**
- Periksa koneksi internet (Supabase adalah cloud database)
- Pastikan kredensial Supabase di `.env` benar

**Rekomendasi tidak muncul setelah login**
- Install Python: `pip install -r python_engine/requirements.txt`
- Atau login sebagai user demo `1@gmail.com` (rekomendasi sudah ada di Supabase)

**Error SQLite / session**
- Jalankan ulang: `php artisan project:setup`

## Lisensi

MIT — Proyek Tugas Akhir.
