# Sistem Rekomendasi Berbasis Machine Learning untuk Menangani Masalah Anti Fomo — ntvnews.id

Proyek **Skripsi** sebagai syarat kelulusan. Repository ini berisi pengembangan ulang sistem rekomendasi pada portal berita **[ntvnews.id](https://ntvnews.id)** — dari pendekatan **konvensional** menuju sistem berbasis **kecerdasan buatan (AI)**.

## Latar Belakang

Sistem rekomendasi yang ada di ntvnews.id saat ini masih menggunakan pendekatan konvensional. Melalui penelitian ini, kami membangun prototipe aplikasi web yang menerapkan dan **membandingkan tiga pendekatan rekomendasi**:

| Metode | Jenis | Deskripsi Singkat |
|--------|-------|-------------------|
| **Popularity-Based Filtering** | Konvensional / Baseline | Mengurutkan artikel berdasarkan popularitas (jumlah interaksi atau views) |
| **ALS** *(Alternating Least Squares)* | Collaborative Filtering | Memfaktorkan matriks interaksi user–artikel untuk menemukan pola preferensi laten |
| **Graph Convolutional Network (GCN)** | Deep Learning | Memodelkan hubungan user–artikel sebagai graf dan mempelajari embedding melalui konvolusi graf (implementasi: LightGCN) |

Tujuan perbandingan ketiga metode ini adalah menemukan pendekatan rekomendasi yang paling efektif untuk konteks portal berita ntvnews.id.

## Pembagian Kerja

| Anggota | Peran | Kontribusi |
|---------|-------|------------|
| **Jeldy Joshua Krisdianto** | Web Developer | Pengembangan aplikasi web (Laravel), antarmuka pengguna, integrasi Supabase, dan orkestrasi pipeline rekomendasi |
| **Bryan Sean Abner** | Machine Learning | Perancangan dan implementasi model rekomendasi (Popularity, ALS, GCN), training, evaluasi, serta inference |

## Stack Teknologi

Prototipe aplikasi web dibangun dengan **Laravel 13**, **Supabase**, dan **Python** (PyTorch, scikit-learn, pandas).

```
Browser (Blade + Supabase JS) ──► Supabase Cloud (data utama)
Laravel (Auth, routing, session) ──► Supabase REST API
Python Engine ──► Training & generate rekomendasi ──► Supabase
SQLite lokal ──► Session, cache, queue Laravel saja
```


## Lisensi

MIT — Proyek Skripsi / Tugas Akhir.
