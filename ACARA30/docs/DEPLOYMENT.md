# Acara 28 — Panduan Deployment ke cPanel (Shared Hosting)

Ringkasan alur: **cek pra-deploy → ekspor SQL → buat database di cPanel → unggah → konfigurasi → uji**.
Paket siap unggah dibuat otomatis oleh `scripts/buat_paket_deploy.php`; Anda tetap mengerjakan langkah di cPanel sendiri.

## 0. Persiapan lokal (Laragon)
```
cd C:\laragon\www\ACARA16-25\ACARA30
php scripts\cek_predeploy.php
php scripts\buat_paket_deploy.php --domain=https://DOMAIN-ANDA --db=NAMACPANEL_dbworkshop --dbuser=NAMACPANEL_dbuser --dbpass=PASSWORD_DARI_CPANEL
```
(Dua baris, jalankan satu per satu. Jika `php` tidak dikenali, buka **Terminal** dari menu Laragon.)
Hasil: folder `deploy_paket\` berisi `app\`, `public_html\`, `db_workshop_hosting.sql`, `PETUNJUK_UPLOAD.txt`.
Tanpa opsi, nilai placeholder modul dipakai (`namacpanel_dbworkshop`, `namacpanel_dbuser`, `password_sesuai_cpanel`) dan Anda mengubah `Database.php` sendiri di File Manager (Langkah 5 modul).

## Langkah 1 — Checklist: `docs/CHECKLIST_PREDEPLOY.md`

## Langkah 2 — Ekspor basis data
- Modul: phpMyAdmin lokal → pilih `db_workshop_acara30` → **Export** → Quick/SQL → berkas `db_workshop.sql`. (Untuk bukti screenshot.)
- Untuk diunggah ke hosting gunakan **`deploy_paket\db_workshop_hosting.sql`**: sama dengan hasil ekspor tetapi tanpa `CREATE DATABASE`/`USE` (user cPanel tidak boleh membuat database) dan tanpa akun seed berpassword publik.
- Jika memakai hasil ekspor phpMyAdmin sendiri, hapus baris `CREATE DATABASE` dan `USE` terlebih dahulu.

## Langkah 3 — Database di cPanel
1. cPanel → **MySQL® Databases** → *Create New Database* (mis. `dbworkshop`) → nama jadi `namacpanel_dbworkshop`.
2. *MySQL Users* → *Add New User* (mis. `dbuser` → `namacpanel_dbuser`) dengan password kuat (gunakan Password Generator, catat).
3. *Add User To Database* → pilih user dan database → **ALL PRIVILEGES** → Make Changes.
4. cPanel → **phpMyAdmin** → klik database `namacpanel_dbworkshop` di kiri → tab **Import** → pilih `db_workshop_hosting.sql` → Import. Tiga tabel muncul: `mahasiswa`, `buku`, `users`.

## Langkah 4 — Unggah struktur proyek (File Manager)
Struktur target:
```
/home/NAMACPANEL/
├── app/            <- dari deploy_paket\app   (DI LUAR public_html)
└── public_html/    <- ISI deploy_paket\public_html (index.php, .htaccess, .user.ini, views/, uploads/)
```
1. File Manager → Settings → centang **Show Hidden Files (dotfiles)**.
2. Unggah `app` (zip-kan lalu *Extract* agar cepat) ke `/home/NAMACPANEL/`, **sejajar** dengan `public_html`.
3. Unggah **isi** `public_html` (bukan foldernya) ke dalam `public_html`. Pastikan `.htaccess` dan `.user.ini` ikut.
4. Izin: folder 755, berkas 644, `public_html/uploads` 755 (naikkan ke 775 bila foto gagal tersimpan).
5. Bila paket hosting mendukung **Document Root** per domain (cPanel → Domains): unggah `public_html\` sebagai folder `public` sejajar dengan `app`, lalu arahkan Document Root ke folder itu. Strukturnya tetap `app/` + folder web; kode tidak perlu diubah karena `PUBLIC_DIR` mengikuti lokasi `index.php`.

## Langkah 5 — Konfigurasi produksi
Sudah diisi oleh `buat_paket_deploy.php`; verifikasi di File Manager:
- `app/config/Database.php`: `$db`, `$user`, `$pass` = nilai cPanel (`$host = 'localhost'`).
- `public_html/views/app.js` dan `common.js`: `BASE_URL_BAWAAN` = `https://DOMAIN-ANDA`. (Pada hosting, `BASE_URL` selalu dihitung dari alamat halaman, sehingga konstanta ini hanya cadangan.)
- `app/config/secret.local.php`: kunci JWT acak khusus server (jangan dibagikan, jangan ke Git).
- `app/config/app.php`: `APP_ENV` default `production`.
- `public_html/index.php`: header CORS `*` dinonaktifkan (View dan API satu origin).

### Membuat admin (akun seed tidak ada di hosting)
Opsi 1 — SSH/Terminal cPanel: `php /home/NAMACPANEL/scripts/buat_admin.php admin admin@domain.id 'PasswordKuat123'` (unggah `scripts/buat_admin.php` sementara, hapus setelahnya).
Opsi 2 — tanpa SSH: lokal jalankan
`php -r "echo password_hash('PasswordKuat123', PASSWORD_BCRYPT);"`
lalu di phpMyAdmin hosting → tab **SQL**:
```sql
INSERT INTO users (username, email, password, role)
VALUES ('admin', 'admin@domain.id', 'HASH_DARI_LANGKAH_SEBELUMNYA', 'admin');
```
Pengguna biasa mendaftar lewat halaman `/register` (role selalu `user`).

## Langkah 6 — Pengujian pasca-deployment
Ikuti `docs/TEST_CASE_PASCA_DEPLOY.md`. Inti yang diminta modul:
1. Buka `https://DOMAIN/views/login.html`, login, pastikan Dashboard menampilkan data (lewat `MahasiswaController`).
2. Postman: environment **Production** (`Production.postman_environment.json`, ubah `base_url` dan `admin_password`), jalankan ulang sebagian test case Acara 23.
3. Buka `https://DOMAIN/app/config/Database.php` → harus **404/403** (bukti MVC terpisah).

## Tugas mandiri
1. Environment Postman **Production**: Import `Production.postman_environment.json` (Environments → Import), ganti `base_url`, pilih environment di pojok kanan atas.
2. Kendala dan solusi: `docs/KENDALA_DEPLOYMENT.md` (bagian A sudah terisi dari simulasi; lengkapi B dan C dengan pengalaman Anda).

## VPS (catatan, di luar praktik)
Pada VPS Anda mengelola sendiri Apache/Nginx, PHP, MySQL; `app/` ditaruh di luar `DocumentRoot` yang menunjuk ke `public/`, `AllowOverride All` untuk `.htaccess`, firewall, dan pembaruan keamanan.
