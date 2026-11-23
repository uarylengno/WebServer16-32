# Acara 28 — Tugas Mandiri 2: Kendala Deployment dan Solusinya

Bagian A adalah kendala yang **sudah ditemukan dan diperbaiki** saat proyek disimulasikan pada tata letak hosting (lihat `docs/acara28/HASIL_SIMULASI_ACARA28.md`).
Bagian B adalah kendala yang **lazim terjadi di cPanel**; centang bila Anda mengalaminya dan lengkapi dengan bukti (screenshot/pesan galat).
Bagian C adalah tabel kosong untuk kendala lain yang Anda temui sendiri.

## A. Ditemukan saat simulasi

| No | Gejala | Penyebab | Solusi |
|----|--------|----------|--------|
| A1 | Foto berhasil diunggah (HTTP 201) tetapi tidak tampil; folder `uploads/` di `public_html` kosong | `MahasiswaController` menulis ke `__DIR__ . '/../../public/uploads/'`. Di hosting tidak ada folder `public/`: `app/` sejajar dengan `public_html/`, sehingga PHP membuat folder `public/uploads` baru **di luar document root** | `public/index.php` mendefinisikan `PUBLIC_DIR` (= folder document root) dan `folderUpload()` memakainya. Berlaku untuk lokal (`public/`) maupun hosting (`public_html/`) |
| A2 | `Koneksi database gagal` (500) | `Database.php` masih memuat nama database lokal `db_workshop_acara30` / user `root` | Isi `$db`, `$user`, `$pass` sesuai cPanel (dikerjakan otomatis oleh `buat_paket_deploy.php`) |
| A3 | Import SQL gagal: `Access denied ... CREATE DATABASE` | User cPanel tidak boleh membuat database; SQL lokal berisi `CREATE DATABASE` dan `USE` | SQL hosting tanpa `CREATE DATABASE`/`USE`; pilih database lebih dulu di phpMyAdmin lalu Import |
| A4 | Token dapat dipalsukan | Kunci JWT bawaan tertulis di `Auth.php` | `app/config/secret.local.php` berisi kunci acak 64 karakter; uji: token yang ditandatangani kunci bawaan → 401 |
| A5 | Akun admin berpassword publik | Seed `admin / Admin@12345` ada di SQL dan dokumentasi | SQL hosting tanpa seed; admin dibuat dengan `scripts/buat_admin.php` |
| A6 | `https://DOMAIN/.htaccess` dan berkas titik dapat dibuka (200) | Pola `FilesMatch` `^(\.|...)$` hanya cocok dengan nama berkas "." | Diperbaiki menjadi `^(\..*|...)$`; ditemukan oleh `scripts/cek_produksi.php` (Acara 30) |
| A7 | Header `X-Powered-By` membuka versi PHP | `expose_php` aktif dan tidak dapat diubah lewat `.user.ini` | `header_remove('X-Powered-By')` di `index.php` |
| A8 | Pengalihan HTTPS berulang tanpa henti (di balik proxy/CDN) | SSL diakhiri di proxy, `%{HTTPS}` selalu off | Tambah `RewriteCond %{HTTP:X-Forwarded-Proto} !https` |
| A9 | `Koneksi database gagal` di Rumahweb | `$host` berisi URL domain, user belum ALL PRIVILEGES, atau password cPanel tidak sama dengan `Database.php` | `$host = 'localhost'`; Add User To Database + ALL PRIVILEGES; samakan password; uji dengan `tes_db.php` sementara lalu hapus |
| A10 | Login: `Username atau password salah` padahal benar | Admin di-INSERT dengan password teks biasa; `password_verify()` butuh hash bcrypt | `UPDATE users SET password='<hash dari password_hash()>'` |
| A11 | Isi proyek (app, docs, scripts, zip, SQL) dapat diunduh publik | Zip proyek diekstrak ke `public_html` | Bersihkan `public_html`; unggah `app/` sejajar `public_html`; hapus zip |

## B. Kendala lazim di cPanel (centang yang Anda alami)

| ☐ | Gejala | Penyebab | Solusi |
|---|--------|----------|--------|
| ☐ | Nama database/user ditolak atau koneksi gagal padahal nama benar | cPanel menambahkan awalan akun (`namacpanel_`) pada nama database dan user | Pakai nama lengkap berawalan di `Database.php` |
| ☐ | Access denied for user | User belum ditambahkan ke database, atau password salah | MySQL Databases → *Add User To Database* → All Privileges; atur ulang password |
| ☐ | Halaman 404 pada semua endpoint (`/mahasiswa`) | `.htaccess` tidak ikut terunggah (berkas titik tersembunyi) atau `mod_rewrite` mati | File Manager → Settings → *Show Hidden Files*; unggah ulang `.htaccess` |
| ☐ | 401 "Token tidak ditemukan" padahal sudah login | Server meneruskan header `Authorization` tidak utuh (CGI/FastCGI) | Aturan `RewriteRule .* - [E=HTTP_AUTHORIZATION:...]` di `.htaccess` sudah ada; pastikan tidak terhapus |
| ☐ | 500 Internal Server Error setelah menaruh `.htaccess` | Direktif `php_value` di `.htaccess` pada PHP-FPM/LiteSpeed | Pakai `.user.ini` (sudah disediakan); lihat `error_log` di File Manager |
| ☐ | Foto gagal diunggah (500/Gagal menyimpan) | Izin folder `uploads/` atau ekstensi `exif` mati | Izin 755 (atau 775); aktifkan `exif` di Select PHP Version |
| ☐ | Unggah foto 2MB ditolak sebelum sampai ke PHP | `upload_max_filesize`/`post_max_size` rendah | `.user.ini` mengatur 3M/4M; tunggu ±5 menit agar cache `.user.ini` diperbarui |
| ☐ | Halaman login tampil tetapi data tidak termuat | `BASE_URL` masih mengarah ke localhost; atau campuran `http`/`https` (mixed content) | Perbaiki `BASE_URL_BAWAAN` / akses lewat `https`; aktifkan SSL lalu aktifkan pengalihan HTTPS |
| ☐ | `domain/app/config/Database.php` menampilkan isi/kosong, bukan 404/403 | `app/` terunggah di dalam `public_html` | Pindahkan `app/` sejajar dengan `public_html`; cadangannya `app/.htaccess` (Deny all) |
| ☐ | Daftar isi folder tampil saat membuka `/uploads/` atau `/views/` | `Options -Indexes` hilang | `public_html/.htaccess` memuatnya |
| ☐ | Import SQL timeout/ukuran terlalu besar | Batas phpMyAdmin | Ekspor tanpa data uji; atau impor per tabel |
| ☐ | Jam pada `timestamp` berbeda | Zona waktu server | `date_default_timezone_set('Asia/Jakarta')` sudah ada di `app.php` |

## C. Kendala lain yang saya temui sendiri

| No | Gejala (sertakan screenshot/pesan galat) | Penyebab | Solusi | Bukti |
|----|-------------------------------------------|----------|--------|-------|
| C1 | | | | |
| C2 | | | | |
| C3 | | | | |
