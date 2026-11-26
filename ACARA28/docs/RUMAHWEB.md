# Acara 28 — Deployment ke Rumahweb (cPanel), langkah demi langkah

Paket Entry Rumahweb memakai cPanel, SSL gratis (Let's Encrypt), dan akses SSH (menurut halaman paket mereka), sehingga seluruh langkah modul dapat dikerjakan apa adanya.
Tampilan cPanel dapat sedikit berbeda; yang dicari adalah nama menunya.

## Tahap 1 — Pesan dan login cPanel
1. Pesan paket hosting, daftarkan atau pakai domain, bayar. Tunggu email berisi data login cPanel.
2. Login cPanel. **Catat:** domain, dan **username cPanel** (mis. `abcd1234`). Username inilah awalan nama database dan user: `abcd1234_dbworkshop`, `abcd1234_dbuser`.
3. Domain baru dapat butuh beberapa menit sampai beberapa jam sebelum aktif.

## Tahap 2 — Database (Langkah 3 modul)
1. cPanel → **MySQL® Databases** → *Create New Database*: `dbworkshop` → jadi `abcd1234_dbworkshop`.
2. *MySQL Users* → *Add New User*: `dbuser` + password kuat (Password Generator, catat) → jadi `abcd1234_dbuser`.
3. *Add User To Database* → pilih user dan database → **ALL PRIVILEGES** → Make Changes.

## Tahap 3 — Paket di laptop (Terminal Laragon, dua baris terpisah)
```
cd C:\laragon\www\ACARA16-25\ACARA28
php scripts\buat_paket_deploy.php --domain=https://DOMAIN-ANDA --db=abcd1234_dbworkshop --dbuser=abcd1234_dbuser --dbpass=PASSWORD_DATABASE
```
(Perintah kedua satu baris.) Hasil di `deploy_paket\`: `app\`, `public_html\`, `db_workshop_hosting.sql`, `PETUNJUK_UPLOAD.txt`.
Untuk screenshot Langkah 2 modul, ekspor juga lewat phpMyAdmin lokal (tab Export) menjadi `db_workshop.sql`.

## Tahap 4 — Impor SQL (Langkah 3 modul)
cPanel → **phpMyAdmin** → klik database `abcd1234_dbworkshop` di kiri → tab **Import** → `db_workshop_hosting.sql` → Go. Tiga tabel muncul: `mahasiswa`, `buku`, `users`.

## Tahap 5 — Unggah (Langkah 4 modul)
1. cPanel → **File Manager** → Settings → centang **Show Hidden Files (dotfiles)**.
2. Zip folder `deploy_paket\app` dan isi `deploy_paket\public_html` secara terpisah di laptop (agar unggah cepat).
3. Di `/home/USERNAME/` (sejajar `public_html`, **bukan** di dalamnya): Upload zip `app` → klik kanan → Extract. Hasil: `/home/USERNAME/app/`.
4. Masuk `public_html`, hapus berkas bawaan (mis. `index.html`/`cgi-bin` boleh dibiarkan), Upload zip isi `public_html` → Extract. Pastikan `.htaccess` dan `.user.ini` ada.
5. Izin: folder 755, berkas 644; `public_html/uploads` 755 (naikkan ke 775 bila foto gagal tersimpan).

## Tahap 6 — Konfigurasi (Langkah 5 modul)
Sudah terisi oleh skrip; verifikasi untuk screenshot: `app/config/Database.php` (host `localhost`, nama database, user, password) dan `public_html/views/app.js` / `common.js` (`BASE_URL_BAWAAN` = domain).
Cek versi PHP: cPanel → **Select PHP Version** → 8.0 atau lebih baru; ekstensi `pdo_mysql`, `exif`, `mbstring`, `fileinfo` aktif.

## Tahap 7 — Admin
Terminal cPanel atau SSH: `php /home/USERNAME/scripts/buat_admin.php admin emailanda@domain.id PasswordKuat123` (unggah `scripts/buat_admin.php` sementara ke `/home/USERNAME/scripts/`, hapus setelah dipakai).
Alternatif tanpa terminal: lihat `docs/DEPLOYMENT.md` bagian "Membuat admin" (SQL di phpMyAdmin).

## Tahap 8 — SSL dan uji (Langkah 6 modul)
1. cPanel → **SSL/TLS Status** → centang domain → **Run AutoSSL**. Setelah terbit, buka `https://DOMAIN`.
2. Browser: `https://DOMAIN/views/login.html` → login → Dashboard menampilkan data.
3. Postman: environment **Production** (`base_url` = `https://DOMAIN`).
4. `https://DOMAIN/app/config/Database.php` harus **404/403**.
5. `docs/TEST_CASE_PASCA_DEPLOY.md` P01-P16 diisi dari hasil di atas.
