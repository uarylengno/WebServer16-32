# Acara 30: Memasang versi produksi di Rumahweb (cPanel)

Panduan ini untuk **memperbarui situs Acara 28** yang sudah berjalan di `https://uarylbkpm.my.id` menjadi versi Acara 30 (HTTPS, header keamanan, display_errors Off, pembatasan akses).
Basis data `uars3355_db_workshop_acara28` dan akun admin **tetap dipakai**, jadi **tidak ada impor SQL**.

## 1. Cadangkan dulu
1. phpMyAdmin → pilih database → **Export** → simpan `.sql`.
2. File Manager: unduh isi `public_html/uploads/` bila sudah ada foto.

## 2. Buat paket (Terminal Laragon, di folder ACARA30)
```
cd C:\laragon\www\ACARA16-25\ACARA30
php scripts\buat_paket_deploy.php --domain=https://uarylbkpm.my.id --db=uars3355_db_workshop_acara28 --dbuser=uars3355_uarylakademik --dbpass="PASSWORD_DATABASE"
```
(Perintah kedua satu baris. Tidak boleh ada tulisan Warning.) Hasil: `deploy_paket\app`, `deploy_paket\public_html`.

## 3. Ganti berkas di hosting
1. File Manager → Settings → **Show Hidden Files**.
2. Di `/home/uars3355/`: ubah nama folder `app` menjadi `app_lama`. Zip `deploy_paket\app` → unggah → Extract. Hasilnya `/home/uars3355/app/`.
3. Di `public_html`: hapus isinya **kecuali** `cgi-bin` dan `uploads` (agar foto lama tetap ada). Zip **isi** `deploy_paket\public_html` → unggah → Extract (timpa bila ditanya).
4. Hapus zip yang diunggah. Setelah situs berjalan, hapus `app_lama`.

## 4. SSL dan HTTPS
cPanel → **SSL/TLS Status** → **Run AutoSSL** untuk domain. Paket sudah mengaktifkan pengalihan HTTPS di `.htaccess`.

## 5. Uji
1. `https://uarylbkpm.my.id/views/login.html` → login admin (token lama tidak berlaku karena kunci rahasia baru).
2. `http://uarylbkpm.my.id` otomatis ke `https://`.
3. `/app/config/Database.php` dan `/app/logs/php_errors.log` harus 403/404.
4. Di laptop: `php scripts\cek_produksi.php https://uarylbkpm.my.id`.
5. Isi `docs/TEST_CASE_PRODUKSI.md` dan `docs/CHECKLIST_AKHIR.md`.

## Jika bermasalah
Lihat `docs/KENDALA_DEPLOYMENT.md` (A9 koneksi database, A10 login ditolak, A11 isi proyek di public_html).
