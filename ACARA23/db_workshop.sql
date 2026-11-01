-- Acara 19: Postman dan API Authentication (melanjutkan Acara 17-18)
-- Import lewat phpMyAdmin (tab Import) atau: mysql -u root < db_workshop.sql

CREATE DATABASE IF NOT EXISTS db_workshop;
USE db_workshop;

DROP TABLE IF EXISTS mahasiswa;
DROP TABLE IF EXISTS buku;
DROP TABLE IF EXISTS users;

-- Resource utama (langkah 3 modul).
-- UNIQUE KEY pada nim = lapisan pengaman kedua untuk tugas mandiri no. 2
-- (validasi utama tetap dilakukan di Model::nimExists()).
CREATE TABLE mahasiswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nim VARCHAR(15) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    prodi VARCHAR(50) NOT NULL,
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
