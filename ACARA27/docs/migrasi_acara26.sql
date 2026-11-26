-- Acara 26, Langkah 1: menambahkan kolom foto pada tabel mahasiswa YANG SUDAH ADA (data lama tetap aman).
-- Jalankan di phpMyAdmin: pilih database yang dipakai proyek (mis. db_workshop_acara27) -> tab SQL -> tempel -> Go.
-- Jalankan SATU KALI saja; menjalankannya lagi menghasilkan error "Duplicate column name 'foto'", itu wajar.
ALTER TABLE mahasiswa ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER prodi;
