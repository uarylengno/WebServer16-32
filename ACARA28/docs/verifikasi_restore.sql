-- Acara 26, Langkah 6 nomor 2: verifikasi hasil restore.
-- Jalankan pada database HASIL RESTORE (pilih dulu di phpMyAdmin), lalu bandingkan angkanya dengan database asli.

-- 1) Tabel yang harus ada: buku, mahasiswa, users
SHOW TABLES;

-- 2) Jumlah baris tiap tabel (harus sama dengan database asli)
SELECT 'users'     AS tabel, COUNT(*) AS baris FROM users
UNION ALL SELECT 'mahasiswa', COUNT(*) FROM mahasiswa
UNION ALL SELECT 'buku',      COUNT(*) FROM buku;

-- 3) Kolom foto (Acara 26) ikut terpulihkan, beserta nama berkas yang terkait
SHOW COLUMNS FROM mahasiswa LIKE 'foto';
SELECT COUNT(*) AS mahasiswa_berfoto FROM mahasiswa WHERE foto IS NOT NULL;

-- 4) Akun awal masih ada dengan role yang benar
SELECT username, role FROM users ORDER BY id;
