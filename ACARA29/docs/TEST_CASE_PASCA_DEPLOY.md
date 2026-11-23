# Acara 28 — Langkah 6: Pengujian Pasca-Deployment

Environment Postman: **Production** (`Production.postman_environment.json`, ubah `base_url` ke domain Anda, isi `admin_password`).
Jalankan request Login (admin) lebih dulu agar `admin_token` terisi (berlaku 5 menit).

| ID | Uji | Langkah | Hasil diharapkan | Hasil aktual | Status |
|----|-----|---------|------------------|--------------|--------|
| P01 | Domain dapat diakses | Browser: `https://DOMAIN/views/login.html` | Halaman login tampil | | |
| P02 | View memuat data lewat MahasiswaController | Login admin → Dashboard | Tabel mahasiswa tampil | | |
| P03 | GET /mahasiswa | Postman GET `{{base_url}}/mahasiswa` | 200, `success: true` | | |
| P04 | Login valid | POST `/login` admin | 200 + token | | |
| P05 | Login password salah | POST `/login` | 401 | | |
| P06 | Tambah (admin) | POST `/mahasiswa` form-data nim/nama/prodi + foto | 201 | | |
| P07 | NIM duplikat | POST nim yang sama | 409 | | |
| P08 | Tambah tanpa token | POST tanpa Authorization | 401 | | |
| P09 | Tambah oleh role user | POST dengan token user | 403 | | |
| P10 | Token kedaluwarsa | Pakai `{{expired_token}}` | 401 | | |
| P11 | Foto tampil | Buka alamat foto dari dashboard | Gambar tampil (200) | | |
| P12 | Eksekusi skrip di uploads ditolak | Buka `/uploads/apa-saja.php` | 403/404 | | |
| P13 | **Pemisahan MVC** | Browser: `https://DOMAIN/app/config/Database.php` | **404 atau 403** | | |
| P14 | Kunci JWT | Buka `https://DOMAIN/app/config/secret.local.php` | 404 atau 403 | | |
| P15 | Daftar folder dinonaktifkan | Buka `https://DOMAIN/uploads/` dan `/views/` | 403/404, bukan daftar berkas | | |
| P16 | HTTPS | Buka `http://DOMAIN/...` (setelah pengalihan aktif) | Dialihkan ke `https://` | | |
