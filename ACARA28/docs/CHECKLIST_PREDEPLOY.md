# Acara 28 — Langkah 1: Checklist Pra-Deployment

Jalankan dari folder proyek: `php scripts/cek_predeploy.php` (di Laragon: Terminal Laragon, `cd C:\laragon\www\ACARA16-25\ACARA28`).
Skrip memeriksa butir yang dapat diperiksa mesin; butir lain dicentang manual.

| No | Butir | Cara memeriksa | Status |
|----|-------|----------------|--------|
| 1 | Seluruh test case Acara 23 berstatus Pass | `docs/TEST_CASE.md` (26 dari 26 Pass setelah retest); skrip membaca status akhir tiap baris | ☐ |
| 2 | Tidak ada kredensial database di luar `app/config/Database.php` | skrip mencari `new PDO`, `mysqli_connect`, password literal di `app/`, `public/` | ☐ |
| 3 | Kunci JWT tidak bergantung pada kunci bawaan di kode | di server ada `app/config/secret.local.php` (dibuat otomatis oleh `buat_paket_deploy.php`) atau environment `JWT_SECRET` | ☐ |
| 4 | Akun seed berpassword publik (`Admin@12345`) tidak dipakai di produksi | SQL hosting tidak memuatnya; admin dibuat dengan `scripts/buat_admin.php` | ☐ |
| 5 | `APP_ENV` = production, CORS tidak `*` | dilakukan otomatis oleh paket hosting | ☐ |
| 6 | Foto uji tidak ikut diunggah | paket hosting hanya membuat `uploads/` kosong + `.htaccess` | ☐ |
| 7 | Versi PHP di cPanel ≥ 8.0 dan ekstensi `pdo_mysql`, `exif`, `mbstring`, `fileinfo` aktif | cPanel → Select PHP Version → Extensions | ☐ |
| 8 | Berkas SQL hosting sudah dibuat | `deploy_paket/db_workshop_hosting.sql` | ☐ |

Catatan: `app/config/Database.php` boleh memuat kredensial (sesuai modul), tetapi berkas itu **tidak boleh** dimasukkan ke GitHub publik berisi password hosting. Simpan password hosting hanya di server.
