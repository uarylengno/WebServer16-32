# Acara 30 — Penguatan Lingkungan Produksi

Melanjutkan deployment Acara 28 (`docs/DEPLOYMENT.md`). Semua langkah modul dan tempatnya di proyek:

| Langkah | Isi | Di proyek |
|---------|-----|-----------|
| 1 | SSL lewat AutoSSL | cPanel (manual) |
| 2 | Paksa HTTPS | `public/.htaccess` (blok dikomentari di lokal, **aktif otomatis** di paket hosting) |
| 3 | `display_errors` Off, `log_errors` On | `app/config/app.php`, `.user.ini`, cPanel MultiPHP INI Editor |
| 4 | `.htaccess` penolak di `app/` | `app/.htaccess` dan `app/logs/.htaccess` (`Require all denied`) |
| 5 | Checklist akhir, uji ulang, dokumentasi API | `docs/CHECKLIST_AKHIR.md`, `docs/TEST_CASE_PRODUKSI.md`, `scripts/cek_produksi.php` |

## Membuat paket produksi
```
php scripts\buat_paket_deploy.php --domain=https://DOMAIN-ANDA --db=NAMACPANEL_dbworkshop --dbuser=NAMACPANEL_dbuser --dbpass=PASSWORD
```
Paket (`deploy_paket\`) kini juga berisi `docs\API_DOCUMENTATION.md` dan `docs\openapi.yaml` dengan base URL `https://DOMAIN-ANDA`
(salinan; berkas di proyek tidak diubah). Opsi `--tanpa-https` membuat paket tanpa pengalihan HTTPS.

## Langkah 1 — AutoSSL
1. cPanel → **SSL/TLS Status** → centang domain (dan `www`) → **Run AutoSSL**. Tunggu beberapa menit sampai status berubah menjadi sertifikat valid.
2. Buka `https://DOMAIN`: browser harus menampilkan ikon gembok. Bila domain baru dibuat atau DNS belum menyebar, AutoSSL dapat gagal; ulangi setelah DNS aktif.
3. Hosting gratis biasanya menyediakan SSL gratis lewat menu tersendiri di panelnya; ikuti panduan penyedia.

## Langkah 2 — Paksa HTTPS
Pilihan A: cPanel → **Domains** → *Force HTTPS Redirect* (hidupkan). Pilihan B: aturan di `public_html/.htaccess` (di **atas** aturan Front Controller):
```
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```
Syarat `X-Forwarded-Proto` ditambahkan agar tidak terjadi perulangan pengalihan bila SSL diakhiri di proxy/CDN. **Aktifkan setelah SSL terbit**;
bila `https://` belum berfungsi, pengalihan membuat situs tidak dapat dibuka. Jangan memakai Pilihan A dan B bersamaan tanpa perlu.
Catatan: pengalihan 301 untuk request POST membuat browser/Postman mengirim ulang sebagai GET; karena itu `base_url` Postman harus langsung `https://`.

## Langkah 3 — `display_errors` Off
- cPanel → **MultiPHP INI Editor** → pilih domain → `display_errors = Off`, `log_errors = On`, `error_log = /home/NAMACPANEL/logs/php_errors.log`.
- Tanpa INI Editor pun aman: `app/config/app.php` mengatur `display_errors=0`, `log_errors=1`, dan `error_log` ke `app/logs/php_errors.log`
  (folder di luar jangkauan browser dan dijaga `.htaccess`). Respons ke pengguna hanya berisi pesan umum; rincian galat masuk log.
- Lihat log: File Manager → `app/logs/php_errors.log`. Hapus atau putar log secara berkala (ukuran dapat membesar).

## Langkah 4 — `app/.htaccess`
Berisi `Require all denied` (dengan cadangan sintaks Apache lama). Ini lapisan tambahan: pertahanan utama tetap menaruh `app/` di luar `public_html`.
Uji: `https://DOMAIN/app/config/Database.php` harus 403/404. Pada konfigurasi di mana `app/` di luar document root, jawabannya 404 karena berkasnya memang tidak terjangkau.

## Langkah 5 — Checklist akhir dan uji ulang
Ikuti `docs/CHECKLIST_AKHIR.md`, jalankan `php scripts/cek_produksi.php https://DOMAIN-ANDA`, dan isi `docs/TEST_CASE_PRODUKSI.md` dari Postman (environment Production).

## Temuan saat penyiapan paket (Acara 30)
- Pola `FilesMatch` pada `.htaccess` paket Acara 28 (`^(\.|...)$`) hanya cocok dengan nama berkas "." sehingga berkas titik tidak ditolak oleh aturan itu. Diperbaiki menjadi `^(\..*|.*\.(sql|log|ini|bak|md))$` dan ditemukan oleh `cek_produksi.php`.
- `X-Powered-By` membuka versi PHP: dihapus lewat `header_remove()` di `public/index.php`.
