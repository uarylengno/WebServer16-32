# Acara 30 — Tugas Mandiri 3: Simulasi Pemulihan Bencana (Disaster Recovery)

Tujuan: membuktikan cadangan Acara 26 benar-benar dapat dipakai memulihkan data setelah sebuah tabel hilang.
**Lakukan hanya pada database percobaan**, bukan database aplikasi yang sedang dipakai.

## Cara otomatis (Git Bash / Linux)
```
scripts/simulasi_dr.sh                 # memakai database db_workshop_acara30_dr
```
Skrip membuat database percobaan dari `db_workshop_acara30.sql`, mencatat jumlah baris dan checksum, membuat backup (`scripts/backup_db.sh`),
menjalankan `DROP TABLE mahasiswa`, membuktikan tabel hilang, memulihkan (`scripts/restore_db.sh --force`), lalu membandingkan kondisi.
Pengaman: nama database harus berakhiran `_dr` atau `_uji`.

## Cara manual (Laragon, untuk screenshot)
1. phpMyAdmin → **New** → buat `db_workshop_acara30_dr`; tab **Import** → `db_workshop_acara30.sql` **tidak boleh** dipakai langsung karena memuat `USE db_workshop_acara30`.
   Cara mudah: buka `db_workshop_acara30` → tab **Operations** → *Copy database to* → `db_workshop_acara30_dr` (structure and data).
2. Catat isi awal: tab SQL → `SELECT COUNT(*) FROM mahasiswa;`
3. Backup: Terminal Laragon, dua baris terpisah:
   `cd C:\laragon\www\ACARA16-25\ACARA30` lalu `scripts\backup_db.bat db_workshop_acara30_dr`. Hasil di folder `backup\`.
4. Bencana: phpMyAdmin → `db_workshop_acara30_dr` → tabel `mahasiswa` → **Drop**. Muat ulang: `SELECT * FROM mahasiswa` menghasilkan galat *Table doesn't exist*.
5. Pemulihan: `scripts\restore_db.bat backup\db_workshop_acara30_dr_TANGGAL.sql db_workshop_acara30_pulih`.
   Versi `.bat` hanya memulihkan ke database **baru** (menolak database yang sudah berisi tabel), jadi hasil pemulihan ada di `db_workshop_acara30_pulih`.
   (Versi `.sh` menerima `--force` untuk menimpa database yang sama; itu yang dipakai `simulasi_dr.sh`.)
6. Verifikasi: di `db_workshop_acara30_pulih` jalankan `SELECT COUNT(*) FROM mahasiswa;` dan bandingkan dengan langkah 2. Periksa juga foto:
   `uploads_*.tar.gz` memulihkan berkas foto, karena foto tidak tersimpan di dalam database.
   Setelah selesai, hapus database percobaan (`_dr` dan `_pulih`).

## Memulihkan satu tabel saja (opsi lebih presisi)
Bila hanya satu tabel yang hilang dan tabel lain sudah berisi data baru, jangan restore seluruh dump. Ambil bagian tabel itu dari berkas backup:
```
(echo "SET NAMES utf8mb4; SET TIME_ZONE='+00:00';"; sed -n '/^-- Table structure for table `mahasiswa`/,/^UNLOCK TABLES;/p' backup/BERKAS.sql) | mysql -u root db_workshop_acara30_dr
```
**Penting:** baris `SET TIME_ZONE='+00:00'` wajib disertakan. Dump menyimpan kolom `created_at` (TIMESTAMP) dalam UTC dan baris itu ada di kepala berkas dump;
bila dilewati, waktu `created_at` bergeser (teruji pada simulasi: 05:23:15 menjadi 23:23:15).

## Pelajaran
- Backup yang belum pernah diuji restore belum dapat dipercaya.
- Simpan salinan backup di luar server dan di luar repository publik.
- Backup basis data tidak berisi foto: arsipkan `uploads/` bersamanya.
- Jadwalkan backup berkala di produksi (cPanel → Backup atau Cron Jobs).
