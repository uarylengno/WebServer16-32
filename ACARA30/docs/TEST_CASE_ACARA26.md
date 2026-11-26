# Hasil Pengujian Acara 26 - Upload File dan Manajemen Database

Pengujian dijalankan pada kode Acara 26 dengan PHP 8.3 (built-in server) dan MariaDB 10.11 (kompatibel MySQL), memakai
skrip Python (HTTP nyata) dan Chromium (Playwright). **Total 93 pengujian, 93 lulus.**
Ulangi pengujian utama di Laragon milikmu (Postman collection tersedia) sebelum menulis hasil di laporan.

## A. Backend: upload, validasi, unlink (U01 sampai U49) dan regresi (R01 sampai R12)

| Kode | Skenario | Hasil |
|---|---|---|
| U01 | POST multipart + JPG valid -> 201 | Lulus |
| U02 | nama berkas di-rename unik (foto_<uniqid>.jpg), bukan nama asli | Lulus |
| U03 | berkas tersimpan di public/uploads/ | Lulus |
| U04 | kolom foto di database = nama berkas | Lulus |
| U05 | foto dapat diakses browser (200, image/jpeg) | Lulus |
| U06 | GET /mahasiswa?id memuat field foto | Lulus |
| U07 | POST multipart + PNG valid -> 201, ekstensi .png | Lulus |
| U08 | dua upload bernama sama tidak saling menimpa | Lulus |
| U09 | POST multipart tanpa foto -> 201, foto null | Lulus |
| U10 | POST JSON lama (tanpa foto) tetap 201 | Lulus |
| U11 | ekstensi .jpeg dengan isi PNG diterima, disimpan sesuai isi (.png) | Lulus |
| U12 | shell.php berisi PHP -> 422 | Lulus |
| U13 | evil.jpg berisi PHP (ekstensi dipalsukan) -> 422 | Lulus |
| U14 | double extension shell.php.jpg berisi PHP -> 422 | Lulus |
| U15 | Content-Type dipalsukan image/jpeg untuk .php -> 422 | Lulus |
| U16 | GIF asli dengan nama .jpg -> 422 | Lulus |
| U17 | GIF asli dengan nama .gif -> 422 | Lulus |
| U18 | gambar WEBP/BMP bernama .png -> 422 | Lulus |
| U19 | berkas tanpa ekstensi berisi JPEG valid -> 422 | Lulus |
| U20 | berkas kosong (0 byte) .jpg -> 422 | Lulus |
| U21 | semua penolakan tidak meninggalkan berkas di uploads/ | Lulus |
| U22 | NIM 100000099 tidak pernah tersimpan | Lulus |
| U23 | PNG valid > 2MB -> 422 "Ukuran berkas maksimal 2MB" | Lulus |
| U24 | PNG valid ~1878 KB (< 2MB) -> 201 | Lulus |
| U25 | request > post_max_size (9MB) -> 422 ukuran, bukan error/kosong | Lulus |
| U26 | foto[] (banyak berkas) -> 422, tidak error | Lulus |
| U27 | NIM 7 karakter + foto valid -> 422 dan tidak ada berkas yatim | Lulus |
| U28 | NIM kosong + foto valid -> 400 dan tidak ada berkas yatim | Lulus |
| U29 | NIM duplikat + foto valid -> 409 dan tidak ada berkas yatim | Lulus |
| U30 | upload tanpa token -> 401, tidak ada berkas | Lulus |
| U31 | upload oleh role user -> 403, tidak ada berkas | Lulus |
| U32 | GET oleh role user tetap 200 dan memuat foto | Lulus |
| U33 | POST ?id&_method=PUT + foto baru -> 200 | Lulus |
| U34 | foto baru tersimpan, nama berbeda dari foto lama | Lulus |
| U35 | TUGAS 1: foto lama dihapus dengan unlink() | Lulus |
| U36 | database memuat foto baru dan nama baru | Lulus |
| U37 | update multipart tanpa foto: foto lama dipertahankan | Lulus |
| U38 | PUT JSON tanpa foto: foto dipertahankan | Lulus |
| U39 | update dengan foto tidak valid -> 422, foto lama utuh | Lulus |
| U40 | update dengan NIM milik orang lain + foto -> 409, tidak ada berkas baru, foto lama utuh | Lulus |
| U41 | update id tidak ada + foto -> 404, tidak ada berkas yatim | Lulus |
| U42 | _method=PUT tanpa id -> 400 | Lulus |
| U43 | update + foto oleh role user -> 403 | Lulus |
| U44 | PUT multipart asli -> 400 dengan petunjuk memakai POST + _method=PUT | Lulus |
| U45 | _method selain PUT diabaikan (tidak bisa menghapus lewat POST) | Lulus |
| U46 | DELETE data berfoto -> 200 dan berkas foto ikut terhapus | Lulus |
| U47 | DELETE data tanpa foto -> 200, tidak error | Lulus |
| U48 | foto bernilai ../../app/config/Database.php: DELETE tidak menghapus berkas lain | Lulus |
| U49 | berkas pelindung .htaccess dan .gitkeep di uploads tetap ada | Lulus |
| R01 | PATCH -> 405 + Allow | Lulus |
| R02 | ?id=abc -> 400 | Lulus |
| R03 | GET id tidak ada -> 404 | Lulus |
| R04 | JSON rusak -> 400 | Lulus |
| R05 | /dosen -> 404 JSON | Lulus |
| R06 | POST JSON NIM duplikat -> 409 | Lulus |
| R07 | POST JSON NIM 5 karakter -> 422 | Lulus |
| R08 | POST tanpa token -> 401 | Lulus |
| R09 | POST role user -> 403 | Lulus |
| R10 | OPTIONS -> 204 + CORS | Lulus |
| R11 | login salah -> 401 | Lulus |
| R12 | POST ?_method=PATCH diperlakukan POST biasa (201) | Lulus |

## B. Frontend dashboard di browser (F01 sampai F32)

| Kode | Skenario | Hasil |
|---|---|---|
| F01 | admin masuk dashboard; belum ada data -> "Belum ada data" | Lulus |
| F02 | header tabel memuat kolom Foto | Lulus |
| F03 | dialog: input file + petunjuk "JPG atau PNG, maksimal 2MB" | Lulus |
| F04 | pratinjau tersembunyi sebelum memilih berkas | Lulus |
| F05 | pratinjau tampil setelah memilih berkas (blob:) | Lulus |
| F06 | simpan -> "Data berhasil ditambahkan" | Lulus |
| F07 | request memakai multipart/form-data dengan boundary (diisi browser) | Lulus |
| F08 | URL tambah = POST /mahasiswa | Lulus |
| F09 | satu berkas tersimpan di uploads/ | Lulus |
| F10 | thumbnail tampil di tabel (gambar benar-benar termuat) | Lulus |
| F11 | statistik total = 1 | Lulus |
| F12 | dialog ubah: pratinjau menampilkan foto saat ini | Lulus |
| F13 | petunjuk ubah: "hanya jika ingin mengganti" | Lulus |
| F14 | berkas .txt ditolak di client: pesan + input dikosongkan | Lulus |
| F15 | PNG > 2MB ditolak di client: "Ukuran berkas maksimal 2MB" | Lulus |
| F16 | penolakan di client tidak mengirim request | Lulus |
| F17 | galat client hilang saat memilih berkas sah | Lulus |
| F18 | ubah memakai POST ?id=1&_method=PUT multipart | Lulus |
| F19 | TUGAS 1: foto lama terhapus, foto baru tersimpan | Lulus |
| F20 | thumbnail di tabel berganti ke foto baru | Lulus |
| F21 | nama baru tampil | Lulus |
| F22 | ubah tanpa memilih foto: foto lama dipertahankan | Lulus |
| F23 | data tanpa foto: placeholder huruf pertama (bukan gambar rusak) | Lulus |
| F24 | nama berisi HTML tampil sebagai teks, skrip tidak jalan | Lulus |
| F25 | NIM duplikat (409): pesan di bawah kolom NIM, dialog tetap terbuka | Lulus |
| F26 | penolakan server tidak meninggalkan berkas yatim | Lulus |
| F27 | hapus data: berkas fotonya ikut terhapus dari uploads/ | Lulus |
| F28 | role user: tidak ada tombol Tambah/Ubah/Hapus | Lulus |
| F29 | role user tetap melihat kolom foto | Lulus |
| F30 | viewport 375px: halaman tidak scroll horizontal | Lulus |
| F31 | dialog 375px: tidak melebar | Lulus |
| F32 | tidak ada error tak terduga di konsol browser | Lulus |

## C. Tidak diuji di lingkungan ini

- Skrip `scripts/backup_db.bat` dan `restore_db.bat` (Windows) belum dijalankan; versi `.sh` sudah diuji (lihat `docs/BACKUP_RESTORE.md`).
- phpMyAdmin Export/Import (Langkah 6) dikerjakan manual di komputermu; tangkapan layar menjadi bukti laporan.
- Hasil di atas berasal dari server uji, bukan Laragon; perilaku `.htaccess` di folder uploads bergantung `AllowOverride All`.
