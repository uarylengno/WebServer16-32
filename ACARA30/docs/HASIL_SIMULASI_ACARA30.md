# Acara 30 — Hasil Simulasi (lingkungan pengembangan, bukan cPanel sungguhan)

Simulasi memakai tata letak hosting (`app/` sejajar `public_html/`), database berprefiks dengan user non-root tanpa hak `CREATE DATABASE`,
paket dari `scripts/buat_paket_deploy.php --domain=https://uji.example.id`, server PHP built-in + MariaDB.

| Pemeriksaan | Hasil |
|-------------|-------|
| Paket: `.htaccess` memuat pengalihan HTTPS aktif (3 baris, sebelum aturan Front Controller) dan header keamanan | Ya (isi berkas diperiksa; **aturan belum dieksekusi** karena bukan Apache) |
| Paket: `app/.htaccess`, `app/logs/.htaccess` ada; `php_errors.log` lokal tidak ikut | Ya |
| Paket: salinan `docs/` berbase URL `https://uji.example.id` | Ya |
| `display_errors=1` dari luar + `trigger_error()` | Tidak ada keluaran di layar; baris tercatat di `app/logs/php_errors.log` |
| Database diputus saat request | Klien menerima `Koneksi database gagal` (500); rincian SQLSTATE hanya di log |
| `scripts/cek_produksi.php http://127.0.0.1:8099` (router uji yang meniru FilesMatch) | Tanpa [GAGAL]; [CEK]: HTTP (bukan HTTPS), header keamanan dan X-Powered-By tidak diuji tanpa Apache |
| Temuan oleh pemeriksaan | Pola `FilesMatch` paket Acara 28 tidak menolak dotfile (`.htaccess` terbaca 200) -> diperbaiki |
| Uji browser (Chromium) 58 regresi + 21 pemeriksaan Acara 29 | 79 dari 79 lulus |
| `scripts/simulasi_dr.sh`: DROP TABLE mahasiswa lalu restore | Jumlah baris dan checksum semua tabel sama dengan sebelum bencana |
| Restore satu tabel dari dump tanpa `SET TIME_ZONE` | `created_at` bergeser; dengan `SET TIME_ZONE='+00:00'` benar |

**Tidak teruji di simulasi:** eksekusi `.htaccess` (rewrite, HTTPS, header, FilesMatch, Options), AutoSSL/sertifikat, MultiPHP INI Editor, izin berkas cPanel, LiteSpeed.
Hal-hal itu terbukti saat Anda menjalankan `cek_produksi.php` dan `TEST_CASE_PRODUKSI.md` pada hosting sungguhan.
