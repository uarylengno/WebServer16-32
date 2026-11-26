# Backup, Restore, dan Jadwal Backup (Acara 26)

Dokumen ini mencakup **Langkah 6** modul (backup dan restore lewat phpMyAdmin) dan **Tugas Praktik Mandiri 2**
(jadwal backup setiap akhir sesi praktikum).

## 1. Apa saja yang harus dicadangkan

| Data | Lokasi | Ikut di backup database? |
|---|---|---|
| Tabel `users`, `mahasiswa`, `buku` (termasuk **nama** berkas foto di kolom `foto`) | MySQL, database `db_workshop_acara29` | Ya |
| **Berkas** foto | folder `public/uploads/` | **Tidak** |

Kolom `foto` hanya menyimpan nama berkas. Jika hanya database yang dipulihkan, tabel menunjuk ke foto yang
tidak ada. Karena itu cadangan yang lengkap adalah **database + folder `public/uploads/`**. Script di
`scripts/` mencadangkan keduanya sekaligus.

## 2. Backup lewat phpMyAdmin (Langkah 6 nomor 1)

1. Buka `http://localhost/phpmyadmin`, klik database **db_workshop_acara29** pada panel kiri.
2. Klik tab **Export**.
3. Metode **Quick** sudah cukup. Untuk kontrol lebih, pilih **Custom** lalu pastikan:
   - Format: **SQL**
   - Tabel: semua (`buku`, `mahasiswa`, `users`) tercentang
   - Pada *Object creation options*, **Add CREATE DATABASE / USE statement** dibiarkan **tidak** dicentang
     (supaya berkas dapat dipulihkan ke database dengan nama lain tanpa menimpa yang asli).
4. Klik **Export**. Browser mengunduh `db_workshop_acara29.sql`.
5. Salin juga folder `public/uploads/` (mis. kompres menjadi `.zip`), karena foto tidak ada di berkas `.sql`.

**[SCREENSHOT: tab Export phpMyAdmin dengan format SQL, dan berkas hasil unduhan]**

## 3. Restore ke database percobaan (Langkah 6 nomor 2)

1. Di phpMyAdmin, klik **New** pada panel kiri. Isi nama `db_workshop_uji`, collation `utf8mb4_general_ci`,
   klik **Create**.
2. Pilih database `db_workshop_uji`, klik tab **Import**.
3. Klik **Choose File**, pilih berkas `.sql` hasil backup, biarkan format **SQL**, klik **Import**
   (bagian bawah halaman). Pesan hijau "Import has been successfully finished" menandakan berhasil.
4. **Verifikasi.** Pada database `db_workshop_uji` pastikan:
   - tabel `buku`, `mahasiswa`, `users` ada di panel kiri;
   - jumlah baris tiap tabel sama dengan database asli (klik tab **SQL**, tempel isi
     `docs/verifikasi_restore.sql`, klik **Go**);
   - kolom `foto` ada pada tabel `mahasiswa`.
5. Bila sudah terbukti, `db_workshop_uji` boleh dihapus (**Operations → Drop the database**).

**[SCREENSHOT: database db_workshop_uji berisi tabel users dan mahasiswa setelah import]**

> Jika Import gagal dengan pesan ukuran berkas melebihi batas, naikkan `upload_max_filesize` dan
> `post_max_size` pada `php.ini` (mis. `40M`), lalu restart Apache.

## 4. Backup dan restore lewat script (lebih cepat, bisa dijadwalkan)

Dari folder proyek.

**Windows (Laragon / XAMPP):**

```bat
scripts\backup_db.bat
scripts\restore_db.bat backup\db_workshop_20261029_160000.sql db_workshop_uji
```

**Linux / macOS / Git Bash:**

```bash
scripts/backup_db.sh
scripts/restore_db.sh backup/db_workshop_20261029_160000.sql db_workshop_uji
```

Hasil backup berada di folder `backup/`:

```
backup/db_workshop_20261029_160000.sql     <- isi database
backup/uploads_20261029_160000.tar.gz      <- foto (di Windows berbentuk .zip)
```

Perilaku penting script:

- `backup_db` memakai `mysqldump --single-transaction`, lalu memeriksa bahwa berkas lengkap (ada `CREATE TABLE` dan
  penanda akhir `Dump completed`). Jika gagal, berkas setengah jadi dihapus dan script berhenti dengan kode error.
- `restore_db` **menolak** memulihkan ke database yang sudah berisi tabel, supaya data yang ada tidak tertimpa
  tanpa sengaja (versi `.sh` menyediakan opsi `--force` untuk sengaja menimpa). Nama database hanya boleh huruf,
  angka, dan `_`.
- Jika MySQL memakai kata sandi, set dulu `MYSQL_PWD` (Windows: `set MYSQL_PWD=...`; Linux: `export MYSQL_PWD=...`).
  Kata sandi tidak ditulis di dalam script.

Memulihkan foto: ekstrak arsip `uploads_*.zip` / `.tar.gz` sehingga isinya kembali ke `public/uploads/`.

### Hasil uji script (lingkungan uji, MariaDB 10.11)

| Uji | Hasil |
|---|---|
| Backup database `db_workshop_acara29` + arsip foto | Berhasil, kedua berkas terbentuk |
| Backup database yang tidak ada | Gagal dengan pesan jelas, tidak ada berkas kosong tertinggal |
| Restore ke `db_workshop_uji` (baru) | Berhasil: `users` 2, `mahasiswa` 2, `buku` 0 baris |
| `CHECKSUM TABLE` tabel asli vs hasil restore | Identik untuk `users`, `mahasiswa`, `buku` |
| Restore ke database yang sudah berisi tabel | Ditolak |
| Nama database berisi karakter berbahaya (`x;DROP ...`) | Ditolak |

Script `.sh` diuji langsung. Script `.bat` (Windows) **belum dijalankan** di lingkungan uji (tidak ada Windows di sana),
sehingga perlu dicoba sekali di komputermu. Jika `mysqldump.exe` tidak ditemukan, isi lokasinya pada baris `set DUMP=`.

## 5. Tugas mandiri 2: jadwal backup setiap akhir sesi praktikum

**Ketentuan jadwal**

| Aspek | Ketetapan |
|---|---|
| Kapan | Di akhir **setiap sesi praktikum**, setelah pekerjaan hari itu selesai dan sebelum komputer dimatikan |
| Siapa | Mahasiswa pemilik proyek |
| Apa | Database `db_workshop_acara29` **dan** folder `public/uploads/` |
| Cara | `scripts\backup_db.bat` (atau Export phpMyAdmin pada langkah 2) |
| Penyimpanan | Folder `backup/` di komputer **dan** satu salinan di tempat lain (flash disk atau penyimpanan pribadi) |
| Pengujian | Satu kali restore ke database percobaan setiap beberapa sesi (bagian 3), karena cadangan yang tidak pernah dites belum terbukti berguna |
| Retensi | Simpan minimal cadangan 4 sesi terakhir; sisanya boleh dihapus manual |
| Keamanan | Berkas cadangan berisi hash password dan data mahasiswa: **jangan** diunggah ke GitHub publik (folder `backup/` sudah masuk `.gitignore`) |

**Daftar periksa akhir sesi**

1. Simpan semua pekerjaan dan hentikan pengubahan data.
2. Jalankan `scripts\backup_db.bat`; pastikan muncul dua baris `OK`.
3. Periksa ukuran berkas `.sql` tidak 0 dan namanya memuat tanggal sesi ini.
4. Salin berkas `.sql` dan arsip foto ke penyimpanan kedua.
5. Catat pada tabel log di bawah.

**Format log (isi pada laporan)**

| Sesi / Acara | Tanggal | Berkas cadangan | Ukuran | Disalin ke | Restore diuji |
|---|---|---|---|---|---|
| Acara 26 | ... | `db_workshop_....sql` | ... KB | ... | Ya / Belum |
| Acara 27 | ... | | | | |

**Otomatisasi (opsional).** Jadwal di atas dapat dijalankan otomatis dengan Windows Task Scheduler. Sesuaikan hari dan jam
dengan jadwal praktikum kelasmu (nilai di bawah hanya contoh) dan jalur proyekmu:

```bat
schtasks /Create /TN "Backup db_workshop_acara29" /SC WEEKLY /D WED /ST 16:00 ^
  /TR "C:\laragon\www\sistem-informasi-akademik\scripts\backup_db.bat"
```

Pada Linux, baris `crontab -e` yang setara (contoh: setiap Rabu 16:00):

```
0 16 * * 3  /path/ke/sistem-informasi-akademik/scripts/backup_db.sh
```

Penjadwal otomatis hanya berjalan jika komputer menyala pada jam itu, sehingga pencadangan manual di akhir sesi
tetap menjadi pegangan utama.
