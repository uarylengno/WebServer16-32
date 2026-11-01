# Laporan Pengujian Manual (Blackbox) API Sistem Informasi Akademik

BKPM Workshop SI Web Server (TIF330805), Acara 23 - Manual Testing dan Blackbox Testing.
Pengujian: Uaryl Engno Laurenca (E41250317), 24 Oktober 2026.

## 1. Ruang Lingkup

Pengujian blackbox terhadap seluruh Controller yang dibangun sejak Acara 17 sampai 20:

| Controller | Endpoint yang diuji |
|---|---|
| AuthController | `POST /login` (`POST /register` diuji pada Acara 19) |
| MahasiswaController | `POST`, `PUT`, `DELETE /mahasiswa` (serta `GET ?id=` sebagai alat verifikasi efek samping) |

Penguji hanya memberi input dan mengamati output (status HTTP, `success`, `message`, `errors`), tanpa melihat kode internal.
Resource `/buku` memakai pola kode yang sama dan tidak diuji ulang pada acara ini.

## 2. Lingkungan Pengujian

| Item | Nilai |
|---|---|
| Base URL | `http://localhost/sistem-informasi-akademik/public` |
| Alat | Postman (Collection dan Environment dari Acara 19), `curl` |
| Pengguna uji | `penguji` (didaftarkan lewat `POST /register`, token dari `POST /login`) |
| Kondisi awal | Database dari `db_workshop.sql` (tabel kosong) |

Catatan kejujuran: hasil aktual pada dokumen ini diperoleh dengan mengirim request HTTP nyata ke aplikasi yang sama,
tetapi pada lingkungan pengembangan yang memakai SQLite sebagai pengganti MySQL (server bawaan PHP). Kode PHP identik.
Sebelum dikumpulkan, jalankan ulang tiap test case di Postman pada XAMPP milikmu dan sesuaikan kolom **Hasil aktual** bila berbeda.

## 3. Partisi Ekuivalen (Equivalence Partitioning)

| Data masukan | Partisi valid | Partisi tidak valid |
|---|---|---|
| `nim` | V1: tepat 9 karakter dan belum dipakai | I1: kosong; I2: kurang dari 9 karakter; I3: lebih dari 9 karakter; I4: sudah dipakai data lain |
| `nama` | V2: 1 sampai 100 karakter | I5: kosong; I6: lebih dari 100 karakter |
| `id` (query) | V3: angka positif dan ada di database | I7: tidak dikirim; I8: bukan angka (`abc`); I9: nol; I10: angka positif tetapi tidak ada (`9999`); I11: berupa array (`id[]=1`) |
| Token | V4: token sah dan belum kedaluwarsa | I12: tidak ada; I13: kedaluwarsa |
| Kredensial login | V5: username dan password benar | I14: password salah |
| Body | V6: JSON valid | I15: bukan JSON |

Satu data uji dipilih mewakili tiap partisi. Untuk `nim` ditambahkan nilai batas 8 dan 10 karakter (TC23 dan TC24).

## 4. Test Case

Kolom **Hasil aktual** berisi status HTTP dan pesan yang benar-benar diterima pada eksekusi akhir (setelah perbaikan pada bagian 6).

### 4.1 Test case dari modul (TC01 sampai TC06)

| ID | Endpoint | Skenario (partisi) | Data uji | Hasil diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC01 | POST /mahasiswa | NIM valid dan lengkap | nim=220100123 | 201 Created | 201 (Data berhasil ditambahkan) | Pass |
| TC02 | POST /mahasiswa | NIM kosong (partisi tidak valid) | nim="" | 400 Bad Request | 400 (Field nim, nama, dan prodi wajib diisi) | Pass |
| TC03 | POST /mahasiswa | NIM duplikat (partisi tidak valid) | nim=220100123 (milik data lain) | 409 Conflict (modul menulis 422, lihat temuan 1) | 409 (NIM sudah terdaftar) | Fail, lalu Pass setelah retest |
| TC04 | POST /login | Login kredensial benar | username=penguji, password benar | 200 OK | 200 (Login berhasil) | Pass |
| TC05 | POST /login | Login kredensial salah | password salah | 401 Unauthorized | 401 (Username atau password salah) | Pass |
| TC06 | POST /mahasiswa | Akses store tanpa token | tanpa header Authorization | 401 Unauthorized | 401 (Token tidak ditemukan) | Pass |

Catatan TC03: lihat bagian 6 (temuan 1).

### 4.2 Test case tambahan: `MahasiswaController::update` (tugas mandiri 1)

| ID | Endpoint | Skenario (partisi) | Data uji | Hasil diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC07 | PUT /mahasiswa?id= | Update dengan data valid | id=1, nama & prodi diubah | 200 OK | 200 (Data berhasil diperbarui) | Pass |
| TC08 | PUT /mahasiswa?id= | ID tidak ada di database | id=9999 | 404 Not Found | 404 (Data tidak ditemukan) | Pass |
| TC09 | PUT /mahasiswa?id= | ID bukan angka | id=abc | 400 Bad Request | 400 (Parameter id wajib berupa angka positif) | Pass |
| TC10 | PUT /mahasiswa?id= | NIM milik mahasiswa lain (duplikat) | id=1, nim=220100124 (milik id=2) | 409 Conflict | 409 (NIM sudah dipakai mahasiswa lain) | Pass |
| TC11 | PUT /mahasiswa?id= | NIM kosong | nim="" | 400 Bad Request | 400 (Field nim, nama, dan prodi wajib diisi) | Pass |
| TC12 | PUT /mahasiswa?id= | NIM kurang dari 9 karakter | nim=2201001 | 422 Unprocessable Entity | 422 (Data tidak lolos validasi) | Pass |
| TC13 | PUT /mahasiswa?id= | Update tanpa token | tanpa header Authorization | 401 Unauthorized | 401 (Token tidak ditemukan) | Pass |
| TC14 | PUT /mahasiswa?id= | NIM sendiri dipertahankan (bukan duplikat) | id=1, nim sama, nama diubah | 200 OK | 200 (Data berhasil diperbarui) | Pass |
| TC15 | PUT /mahasiswa?id= | Nama 101 karakter (di atas batas) | nama=101 karakter | 422 Unprocessable Entity | 422 (Data tidak lolos validasi) | Pass |
| TC16 | PUT /mahasiswa | Tanpa parameter id | tanpa ?id= | 400 Bad Request | 400 (Parameter id wajib berupa angka positif) | Pass |
| TC25 | PUT /mahasiswa?id= | Body bukan JSON valid | body=bukan json | 400 Bad Request | 400 (Body harus berupa JSON yang valid) | Pass |

Pemeriksaan efek samping: TC10 memastikan data `id=1` tetap bernim `220100123` setelah penolakan, dan TC13 memastikan nama tidak berubah setelah request tanpa token.

### 4.3 Test case tambahan: `MahasiswaController::destroy` (tugas mandiri 1)

| ID | Endpoint | Skenario (partisi) | Data uji | Hasil diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC17 | DELETE /mahasiswa?id= | Hapus data yang ada | id=3 | 200 OK | 200 (Data berhasil dihapus) | Pass |
| TC18 | DELETE /mahasiswa?id= | ID tidak ada di database | id=9999 | 404 Not Found | 404 (Data tidak ditemukan) | Pass |
| TC19 | DELETE /mahasiswa?id= | ID bukan angka positif (0) | id=0 | 400 Bad Request | 400 (Parameter id wajib berupa angka positif) | Pass |
| TC20 | DELETE /mahasiswa?id= | Hapus tanpa token | id=2 tanpa header Authorization | 401 Unauthorized | 401 (Token tidak ditemukan) | Pass |
| TC21 | DELETE /mahasiswa?id= | Hapus kedua kali pada ID yang sama | id=3 (sudah dihapus) | 404 Not Found | 404 (Data tidak ditemukan) | Pass |
| TC22 | DELETE /mahasiswa?id= | Hapus dengan token kedaluwarsa | id=2, token exp lampau | 401 Unauthorized | 401 (Token tidak valid atau kedaluwarsa) | Pass |
| TC26 | DELETE /mahasiswa?id[]= | ID berupa array (id[]=1) | id[]=1 | 400 Bad Request | 400 (Parameter id wajib berupa angka positif) | Fail, lalu Pass setelah retest |

Pemeriksaan efek samping: TC17 memastikan `GET ?id=` sesudah penghapusan menghasilkan 404; TC20 dan TC22 memastikan data tidak terhapus bila token tidak ada atau kedaluwarsa.

### 4.4 Nilai batas tambahan: `MahasiswaController::store`

| ID | Endpoint | Skenario (partisi) | Data uji | Hasil diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC23 | POST /mahasiswa | NIM terlalu panjang (10 karakter, batas atas) | nim=2201001234 | 422 Unprocessable Entity | 422 (Data tidak lolos validasi) | Pass |
| TC24 | POST /mahasiswa | NIM terlalu pendek (8 karakter, batas bawah) | nim=22010012 | 422 Unprocessable Entity | 422 (Data tidak lolos validasi) | Pass |

## 5. Ringkasan Hasil

| Eksekusi | Total | Pass | Fail |
|---|---|---|---|
| Run 1 (sebelum perbaikan) | 26 | 24 | 2 |
| Run 2 (retest sesudah perbaikan) | 26 | 26 | 0 |

Regresi: seluruh request pada Collection Postman (27 request) dijalankan ulang sesudah perbaikan dan semuanya sesuai harapan.

## 6. Temuan Fail, Perbaikan, dan Retest (tugas mandiri 2)

### Temuan 1 - TC03: NIM duplikat (bukan bug kode, selisih spesifikasi)

| Item | Isi |
|---|---|
| Diharapkan (modul) | 422 Unprocessable Entity |
| Aktual (run 1) | 409 Conflict, pesan "NIM sudah terdaftar" |
| Penyebab | Modul menulis 422 untuk NIM duplikat, sedangkan sejak Acara 17 sampai 22 proyek ini memakai 409 Conflict untuk konflik data (didokumentasikan di `API_DOCUMENTATION.md` dan `openapi.yaml`, dan diuji oleh Collection Postman Acara 19) |
| Tindakan | **Kode tidak diubah.** Hasil yang diharapkan pada TC03 disesuaikan menjadi 409, karena 409 lebih tepat secara semantik (data valid secara format tetapi bertabrakan dengan data yang ada) dan konsisten dengan seluruh dokumentasi dan pengujian sebelumnya |
| Retest | 409, Pass |

Jika dosen mewajibkan 422 persis seperti modul, ubah `Response::json(409, ...)` menjadi 422 pada `MahasiswaController::store()` dan `update()`
serta pada `Mahasiswa::create()` dan `update()` (blok `23000`), lalu perbarui dokumentasi dan Collection Postman.

### Temuan 2 - TC26: `?id[]=1` memicu PHP Warning (bug nyata, sudah diperbaiki)

| Item | Isi |
|---|---|
| Diharapkan | 400 Bad Request tanpa warning di log server |
| Aktual (run 1) | 400 benar, tetapi log server mencatat `PHP Warning: Array to string conversion` pada `app/core/BaseController.php` |
| Penyebab | `requireId()` mengubah `$id` menjadi string dengan `(string) $id` sebelum memastikan tipenya. Pada `?id[]=1`, PHP memberi `$id` berupa array |
| Dampak | Response ke client tetap benar dan tidak bocor (`display_errors` dimatikan), tetapi log dipenuhi warning dan input array tidak ditangani secara sengaja |
| Perbaikan | Lapisan Controller (`BaseController::requireId`): periksa `is_string($id)` lebih dulu |
| Retest | 400 dan 0 warning baru di log, Pass |

Sebelum:

```php
if ($id === null || $id === '' || !ctype_digit((string) $id) || (int) $id < 1) {
```

Sesudah:

```php
if (!is_string($id) || $id === '' || !ctype_digit($id) || (int) $id < 1) {
```

## 7. Pengamatan Lain (bukan Fail)

- `?id=01` diterima dan diperlakukan sebagai `id=1` (angka nol di depan diabaikan). Tidak membahayakan, hanya dicatat.
- NIM yang dikirim sebagai angka JSON (`220100123` tanpa tanda kutip) diterima dan diperlakukan sebagai string.
- Percobaan injeksi SQL pada `id` (`1 OR 1=1`) dan `nim` ditolak dengan 400 atau 422, dan data tidak berubah (prepared statement).
- Aturan NIM di kode adalah "tepat 9 karakter" (tidak harus angka), sedangkan pesan error menyebut "9 digit".
- Dengan MySQL, pembandingan NIM tidak peka huruf besar/kecil (collation bawaan), sedangkan SQLite yang dipakai pengujian ini peka. Kasus `e41250317` versus `E41250317` perlu kamu coba sendiri di XAMPP.

## 8. Cara Menjalankan di Postman

1. Import `db_workshop.sql` agar tabel kosong, jalankan XAMPP, pilih Environment **Local**.
2. Jalankan `POST /register` lalu `POST /login` (token tersimpan otomatis di variabel `token`).
3. Eksekusi TC sesuai urutan tabel. Data pendukung: buat tiga mahasiswa lebih dulu (`220100123`, `220100124`, `220100125`) bila ingin mengulang TC07 sampai TC22.
4. Catat status HTTP dan pesan yang diterima pada kolom **Hasil aktual**, lalu bandingkan dengan **Hasil diharapkan**.
