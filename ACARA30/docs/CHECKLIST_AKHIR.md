# Acara 30 — Checklist Akhir dan Serah Terima

| No | Butir | Cara memeriksa | Status |
|----|-------|----------------|--------|
| 1 | `php scripts/cek_predeploy.php` tanpa [GAGAL] | Terminal Laragon | ☐ |
| 2 | Paket produksi dibuat dengan `https://` | `scripts/buat_paket_deploy.php --domain=https://...` | ☐ |
| 3 | AutoSSL berhasil, gembok tampil | `https://DOMAIN` di browser | ☐ |
| 4 | `http://` dialihkan ke `https://` | `scripts/cek_produksi.php` / `curl -I http://DOMAIN/mahasiswa` | ☐ |
| 5 | `display_errors` Off, `log_errors` On | MultiPHP INI Editor + uji `POST /login` body rusak | ☐ |
| 6 | Log galat tercatat dan tidak dapat diunduh | `app/logs/php_errors.log` ada; URL-nya 403/404 | ☐ |
| 7 | `app/` tidak terjangkau browser | `https://DOMAIN/app/config/Database.php` 403/404 | ☐ |
| 8 | Kunci JWT khusus server aktif | `cek_produksi.php`: token berkunci bawaan ditolak | ☐ |
| 9 | Admin produksi dibuat dengan password kuat; akun seed tidak ada | phpMyAdmin → tabel `users` | ☐ |
| 10 | Seluruh test case Acara 23 diuji ulang di produksi | `docs/TEST_CASE_PRODUKSI.md` bagian A | ☐ |
| 11 | Dokumentasi API memakai base URL `https://` | `deploy_paket/docs/` (disalin ke repository bila perlu) | ☐ |
| 12 | Backup terbaru dan uji restore berhasil | `scripts/backup_db.sh`, `scripts/simulasi_dr.sh` (database percobaan) | ☐ |
| 13 | `scripts/buat_admin.php` dan berkas sementara dihapus dari server | File Manager | ☐ |
| 14 | Password hosting tidak ada di GitHub | `git log -p` / `.gitignore` memuat `secret.local.php`, `deploy_paket/` | ☐ |
| 15 | Laporan akhir dan bahan presentasi siap | `docs/STRUKTUR_PROYEK.md`, `docs/BAHAN_PRESENTASI.md` | ☐ |
