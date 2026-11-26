# Acara 30 — Langkah 5: Uji Ulang Test Case Acara 23 pada Produksi (HTTPS)

Environment Postman: **Production** (`base_url` = `https://DOMAIN-ANDA`, tanpa garis miring di akhir). Login admin lebih dulu agar `admin_token`
terisi (berlaku 5 menit). Rincian input tiap TC ada di `docs/TEST_CASE.md`. Kolom "Hasil aktual" dan "Status" diisi dari hasil di hosting Anda.

## A. Test case Acara 23 (26 kasus) pada produksi

| ID | Endpoint | Skenario | Diharapkan | Hasil aktual | Status |
|----|----------|----------|------------|--------------|--------|
| TC01 | POST /mahasiswa | NIM valid dan lengkap | 201 Created | | |
| TC02 | POST /mahasiswa | NIM kosong (partisi tidak valid) | 400 Bad Request | | |
| TC03 | POST /mahasiswa | NIM duplikat (partisi tidak valid) | 409 Conflict | | |
| TC04 | POST /login | Login kredensial benar | 200 OK | | |
| TC05 | POST /login | Login kredensial salah | 401 Unauthorized | | |
| TC06 | POST /mahasiswa | Akses store tanpa token | 401 Unauthorized | | |
| TC07 | PUT /mahasiswa?id= | Update dengan data valid | 200 OK | | |
| TC08 | PUT /mahasiswa?id= | ID tidak ada di database | 404 Not Found | | |
| TC09 | PUT /mahasiswa?id= | ID bukan angka | 400 Bad Request | | |
| TC10 | PUT /mahasiswa?id= | NIM milik mahasiswa lain (duplikat) | 409 Conflict | | |
| TC11 | PUT /mahasiswa?id= | NIM kosong | 400 Bad Request | | |
| TC12 | PUT /mahasiswa?id= | NIM kurang dari 9 karakter | 422 Unprocessable Entity | | |
| TC13 | PUT /mahasiswa?id= | Update tanpa token | 401 Unauthorized | | |
| TC14 | PUT /mahasiswa?id= | NIM sendiri dipertahankan (bukan duplikat) | 200 OK | | |
| TC15 | PUT /mahasiswa?id= | Nama 101 karakter (di atas batas) | 422 Unprocessable Entity | | |
| TC16 | PUT /mahasiswa | Tanpa parameter id | 400 Bad Request | | |
| TC25 | PUT /mahasiswa?id= | Body bukan JSON valid | 400 Bad Request | | |
| TC17 | DELETE /mahasiswa?id= | Hapus data yang ada | 200 OK | | |
| TC18 | DELETE /mahasiswa?id= | ID tidak ada di database | 404 Not Found | | |
| TC19 | DELETE /mahasiswa?id= | ID bukan angka positif (0) | 400 Bad Request | | |
| TC20 | DELETE /mahasiswa?id= | Hapus tanpa token | 401 Unauthorized | | |
| TC21 | DELETE /mahasiswa?id= | Hapus kedua kali pada ID yang sama | 404 Not Found | | |
| TC22 | DELETE /mahasiswa?id= | Hapus dengan token kedaluwarsa | 401 Unauthorized | | |
| TC26 | DELETE /mahasiswa?id[]= | ID berupa array (id[]=1) | 400 Bad Request | | |
| TC23 | POST /mahasiswa | NIM terlalu panjang (10 karakter, batas atas) | 422 Unprocessable Entity | | |
| TC24 | POST /mahasiswa | NIM terlalu pendek (8 karakter, batas bawah) | 422 Unprocessable Entity | | |

## B. Pengujian tambahan setelah penguatan (Acara 30)

| ID | Uji | Langkah | Diharapkan | Hasil aktual | Status |
|----|-----|---------|------------|--------------|--------|
| K01 | Sertifikat SSL valid | Buka `https://DOMAIN` | Ikon gembok, tanpa peringatan | | |
| K02 | Pengalihan HTTP ke HTTPS | Buka `http://DOMAIN/views/login.html` | Dialihkan 301 ke `https://` | | |
| K03 | Pengalihan juga untuk API | Postman/curl `-I http://DOMAIN/mahasiswa` | 301, header `Location: https://...` | | |
| K04 | Semua endpoint via HTTPS | Jalankan collection dengan `base_url` https | Hasil sama dengan bagian A | | |
| K05 | `display_errors` Off | Kirim `POST /login` dengan body `{rusak` | JSON galat umum, tanpa jejak PHP/path | | |
| K06 | Galat tercatat di log | File Manager: `app/logs/php_errors.log` (atau error_log cPanel) setelah K05 atau saat DB diputus | Ada baris galat | | |
| K07 | `app/` tidak dapat diakses | `https://DOMAIN/app/config/Database.php` | 403/404 | | |
| K08 | Log tidak dapat diunduh | `https://DOMAIN/app/logs/php_errors.log` | 403/404 | | |
| K09 | Pemeriksaan otomatis | `php scripts/cek_produksi.php https://DOMAIN` | Tanpa baris [GAGAL] | | |
| K10 | Dokumentasi API memakai https | Buka `docs/API_DOCUMENTATION.md` dan `docs/openapi.yaml` salinan paket | Base URL = `https://DOMAIN` | | |
