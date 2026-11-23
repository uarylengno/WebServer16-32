# Deployment ke InfinityFree (hosting gratis) — langkah demi langkah

Pengganti `docs/DEPLOYMENT.md` bila memakai InfinityFree. Tampilan panel dapat sedikit berbeda dari uraian di sini; nama menunya yang dicari.

## Perbedaan dengan cPanel (Acara 28 dan 30)
| Hal | cPanel | InfinityFree |
|-----|--------|--------------|
| Folder web | `public_html` | `htdocs` |
| Folder `app/` | di luar `public_html` | **di dalam `htdocs/app`** (PHP umumnya dibatasi pada folder akun; belum saya uji di akun nyata). Dijaga `app/.htaccess` (Require all denied) |
| Host MySQL | `localhost` | alamat khusus, mis. `sqlXXX.infinityfree.com` (ada di panel) |
| Nama database dan user | awalan akun | awalan `if0_...`; password mengikuti panel |
| SSL | AutoSSL | subdomain gratis sudah ber-SSL; domain sendiri lewat menu *Free SSL Certificates* |
| Force HTTPS | tombol Domains atau `.htaccess` | `.htaccess` (sudah aktif di paket) |
| SSH | ada | tidak ada: admin dibuat lewat SQL |
| Postman | langsung | terhalang proteksi browser; lihat bagian Kendala |

## Tahap 1 — Akun dan situs
1. Daftar di infinityfree.com, verifikasi email, masuk ke Client Area.
2. *Create Account* → pilih subdomain gratis (mis. `namaanda.rf.gd`) → buat. Catat **username akun** (berawalan `if0_`) dan password panel.
3. Tunggu status akun aktif (beberapa menit). Buka *Control Panel* akun.

## Tahap 2 — Database
1. Control Panel → **MySQL Databases** → buat database (mis. `siakademik`). Nama lengkapnya berawalan akun, mis. `if0_12345678_siakademik`.
2. Catat tiga hal: **nama database**, **username MySQL**, **password MySQL**, dan **MySQL Hostname** (`sqlXXX.infinityfree.com`).

## Tahap 3 — Paket di laptop (Terminal Laragon, dua baris terpisah)
```
cd C:\laragon\www\ACARA16-25\ACARA30
php scripts\buat_paket_deploy.php --layout=flat --domain=https://namaanda.rf.gd --dbhost=sqlXXX.infinityfree.com --db=if0_12345678_siakademik --dbuser=if0_12345678 --dbpass=PASSWORD_MYSQL --adminuser=admin --adminemail=emailanda@gmail.com --adminpass=PasswordKuat123
```
(Satu perintah panjang dalam satu baris.) Hasil di `deploy_paket\`:
`htdocs\` (seluruh isi untuk diunggah), `db_workshop_hosting.sql`, `buat_admin.sql` (berisi hash, bukan password), `docs\`.

## Tahap 4 — Impor SQL
Control Panel → **phpMyAdmin** → pilih database → tab **Import** → `db_workshop_hosting.sql`. Lalu tab **SQL** → tempel isi `buat_admin.sql` → Go.
Periksa tabel `mahasiswa`, `buku`, `users` (1 baris admin).

## Tahap 5 — Unggah
1. **Online File Manager** atau FTP (FileZilla; alamat, username, password FTP ada di panel). Aktifkan tampilan berkas tersembunyi.
2. Buka folder **htdocs**, hapus berkas bawaan (mis. `index2.html`).
3. Unggah **isi** `deploy_paket\htdocs\` ke dalam `htdocs`: `index.php`, `.htaccess`, `.user.ini`, folder `app`, `uploads`, `views`. Banyak berkas kecil: bila File Manager lambat, pakai FTP.
4. Pastikan `.htaccess` ikut (berkas titik) dan `htdocs/uploads` dapat ditulis.

## Tahap 6 — SSL dan HTTPS
Subdomain gratis memakai SSL bawaan: buka `https://namaanda.rf.gd`. Pengalihan HTTPS sudah aktif di `.htaccess`. Bila https belum berfungsi,
beri `#` pada tiga baris RewriteCond/RewriteRule HTTPS sampai sertifikat aktif. Domain sendiri: *Free SSL Certificates* (butuh CNAME verifikasi; dapat memakan beberapa jam).

## Tahap 7 — Uji
Browser: `https://namaanda.rf.gd/views/login.html` → login admin → dashboard → tambah data dengan foto.
Cek keamanan: `https://namaanda.rf.gd/app/config/Database.php` harus **403/404** (bukan isi berkas).
Dari laptop: `php scripts\cek_produksi.php https://namaanda.rf.gd` (skrip memakai PHP, bukan browser, sehingga dapat terhalang proteksi browser; lihat bawah).

## Kendala khusus InfinityFree
- **Postman/skrip menerima HTML, bukan JSON:** proteksi browser InfinityFree. Solusi yang lazim: ambil cookie `__test` dari browser (DevTools → Application → Cookies) dan kirim sebagai header `Cookie` di Postman; cookie dapat kedaluwarsa. Catat sebagai kendala di laporan.
- **Foto tidak tersimpan:** periksa izin `htdocs/uploads`.
- **500 setelah mengunggah `.htaccess`:** beri `#` pada baris yang dicurigai (mis. blok `mod_headers`) atau hapus sementara; lihat log di `htdocs/app/logs/php_errors.log`.
- **Mixed content / halaman putih setelah https:** pastikan `BASE_URL_BAWAAN` berawalan `https://` (paket sudah mengisinya dari `--domain`).
- **Situs "tidak aktif":** situs gratis perlu sedikit kunjungan per bulan.
- Disaster recovery (Acara 30): ekspor database lewat phpMyAdmin (tab Export) sebagai backup; uji restore pada database percobaan kedua.
