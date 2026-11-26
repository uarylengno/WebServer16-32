# Acara 23 – Manual Testing dan Blackbox Testing

BKPM Workshop SI Web Server (TIF330805). Melanjutkan proyek Acara 17–22 (`sistem-informasi-akademik`).
README Acara 21 dan 22 diarsipkan di `docs/acara21/` dan `docs/acara22/`.

## Yang berubah dari Acara 22

| Berkas | Perubahan |
|--------|-----------|
| `docs/TEST_CASE.md` | **Baru.** Laporan pengujian: partisi ekuivalen, 26 test case (TC01–TC06 dari modul, TC07–TC26 tugas mandiri), hasil aktual, temuan Fail, perbaikan, dan retest |
| `app/core/BaseController.php` | **Diperbaiki.** `requireId()` kini memeriksa `is_string($id)` lebih dulu, sehingga `?id[]=1` tidak lagi memicu PHP Warning "Array to string conversion" (temuan TC26). Response tetap 400 |
| `app/`, `public/`, `db_workshop.sql`, Postman, `docs/openapi.yaml` | Tidak berubah |

## Hasil ringkas

- Run 1 (sebelum perbaikan): 24 Pass, 2 Fail (TC03, TC26).
- Run 2 (retest): 26/26 Pass. Collection Postman 27/27 request tetap lulus (regresi).
- TC03 (NIM duplikat): modul menulis 422, kode memakai 409 sejak Acara 17. Kode **tidak diubah**, ekspektasi disesuaikan. Alasan dan cara mengubahnya ke 422 ada di `docs/TEST_CASE.md` bagian 6.

## Yang perlu kamu lakukan

1. Import `db_workshop.sql` (tabel kosong), jalankan XAMPP, pilih Environment **Local** di Postman.
2. Jalankan test case satu per satu (lihat `docs/TEST_CASE.md` bagian 8), catat hasil aktual, dan ambil screenshot.
3. Hasil di dokumen berasal dari pengujian di workspace (SQLite). Sesuaikan kolom **Hasil aktual** bila hasil di XAMPP berbeda.

## Catatan

- Keamanan: ganti `Auth::SECRET` (atau set env `JWT_SECRET`) sebelum push ke GitHub publik.
