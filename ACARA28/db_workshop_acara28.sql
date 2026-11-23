-- Acara 28: database db_workshop_acara28 (struktur sama dengan Acara 27).
-- Import lewat phpMyAdmin (tab Import) atau: mysql -u root < db_workshop_acara28.sql
-- Untuk HOSTING (cPanel) jangan pakai berkas ini: gunakan deploy_paket/db_workshop_hosting.sql (dibuat scripts/buat_paket_deploy.php).
-- PERHATIAN: berkas ini memuat DROP TABLE, sehingga SEMUA data lama terhapus.
-- Jika database sudah berisi data dari acara sebelumnya dan ingin dipertahankan, JANGAN import berkas ini;
-- cukup jalankan docs/migrasi_acara26.sql (hanya menambahkan kolom foto).

CREATE DATABASE IF NOT EXISTS db_workshop_acara28;
USE db_workshop_acara28;

DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS buku;
DROP TABLE IF EXISTS users;

-- Resource utama (langkah 3 modul).
-- UNIQUE KEY pada nim = lapisan pengaman kedua untuk tugas mandiri no. 2
-- (validasi utama tetap dilakukan di Model::nimExists()).
-- Acara 26: kolom foto menyimpan NAMA berkas (mis. foto_65f1c2.12345678.jpg), bukan isi gambarnya.
-- Berkasnya ada di public/uploads/. NULL = belum punya foto.
CREATE TABLE mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(15) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    prodi VARCHAR(50) NOT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mahasiswa_nim (nim)
);

-- Resource tambahan (tugas mandiri no. 1)
CREATE TABLE buku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150) NOT NULL,
    penulis VARCHAR(100) NOT NULL,
    tahun SMALLINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Acara 19: tabel pengguna untuk autentikasi.
-- Kolom password menyimpan HASH dari password_hash() (bukan teks asli), 255 karakter
-- disiapkan agar aman jika algoritma PASSWORD_DEFAULT berubah di versi PHP mendatang.
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Acara 25: akun awal untuk RBAC.
-- Pendaftaran lewat POST /register SELALU menghasilkan role 'user', sehingga admin pertama harus dibuat di sini.
-- Password disimpan sebagai hash bcrypt (password_hash), BUKAN teks asli:
--   admin     / Admin@12345   (role admin: boleh Tambah, Ubah, Hapus data mahasiswa)
--   user_demo / User@12345    (role user : hanya boleh melihat data)
-- PENTING: password di atas hanya untuk belajar dan sudah tercantum di dokumen ini. GANTI sebelum
-- aplikasi dipasang di server sungguhan atau diunggah ke GitHub publik, mis. dengan
--   php -r "echo password_hash('PasswordBaruAnda', PASSWORD_DEFAULT);"
-- lalu tempel hasilnya pada kolom password di bawah.
INSERT INTO users (username, email, password, role) VALUES
('admin',     'admin@example.com',     '$2y$10$EFEl0JP4nSVl49GkfWjOJeW1I2Vc024bQxaUAk93k/9/KynlN/DGy', 'admin'),
('user_demo', 'user_demo@example.com', '$2y$10$t/HzmZd.oToQNhAQWUGd3OUqj5viUlGJ0GukMGpO.LGbBqH3rjq3y', 'user');
