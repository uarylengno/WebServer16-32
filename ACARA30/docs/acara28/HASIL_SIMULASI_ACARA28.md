# Acara 28 — Hasil Simulasi Deployment (di lingkungan pengembangan, bukan cPanel sungguhan)

Simulasi memakai tata letak hosting: `app/` sejajar dengan `public_html/` (document root), database `uji_dbworkshop` dengan user `uji_dbuser` **tanpa** hak `CREATE DATABASE`, SQL `db_workshop_hosting.sql`, admin dibuat dengan `scripts/buat_admin.php`, pengguna biasa lewat `POST /register`. Server: PHP 8.3 built-in + MariaDB.

| Pemeriksaan | Hasil |
|-------------|-------|
| Import `db_workshop_hosting.sql` oleh user non-root | Berhasil (3 tabel, 0 baris `users`) |
| User non-root `CREATE DATABASE` | Ditolak (sesuai kondisi cPanel) |
| `GET /app/config/Database.php`, `/app/config/secret.local.php` | 404 |
| `GET /db_workshop_hosting.sql`, `/uploads/` | 404 |
| Token yang ditandatangani kunci bawaan `kunci_rahasia_workshop_2026` | 401 (ditolak) |
| Login admin hasil `buat_admin.php` | 200 + token |
| Uji browser (Playwright/Chromium): 58 pemeriksaan (login, penjaga halaman, role, validasi, tambah/ubah/hapus + foto, toast, keamanan) | **58 lulus, 0 gagal** |
| Temuan | Folder upload tertulis di luar document root (A1) → diperbaiki dengan `PUBLIC_DIR` |

**Tidak teruji di simulasi:** aturan `.htaccess` (rewrite, `Options -Indexes`, FilesMatch, `app/.htaccess`), `.user.ini`, izin berkas cPanel, SSL/HTTPS, dan perilaku LiteSpeed. Hal-hal ini baru terbukti saat Anda menguji di hosting sungguhan (`TEST_CASE_PASCA_DEPLOY.md` P12–P16).
