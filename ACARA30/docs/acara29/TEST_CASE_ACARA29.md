# Acara 29 — Test Case View (Loading State, Escaping, Retry)

Pengujian otomatis di Chromium (Playwright) terhadap PHP dan MariaDB. Gangguan jaringan disimulasikan dengan `page.route()`
(request dibatalkan atau ditunda). Suite lengkap = 58 pemeriksaan regresi Acara 27 (F01-F56) + 21 pemeriksaan baru (G01-G21):
**79 dari 79 Pass**.

| ID | Skenario | Status |
|----|----------|--------|
| G01 | index.html: tag HTML pada nama tampil sebagai teks | Pass |
| G02 | index.html: <img onerror> dan <script> tidak dieksekusi | Pass |
| G03 | dashboard: payload XSS tidak dieksekusi, tampil sebagai teks | Pass |
| G04 | modal hapus: nama berisi HTML tampil sebagai teks | Pass |
| G05 | escapeHTML: & < > " ' diganti, null menjadi teks kosong | Pass |
| G06 | index.html: baris "Memuat data..." dengan spinner di tabel | Pass |
| G07 | index.html: tombol nonaktif dan berlabel "Memuat..." selama loading | Pass |
| G08 | index.html: setelah selesai tombol aktif lagi berlabel "Muat ulang" | Pass |
| G09 | dashboard: baris "Memuat data..." dengan spinner dan tombol Muat ulang bersatus loading | Pass |
| G10 | index.html: gangguan jaringan diulang otomatis satu kali lalu berhasil (2 request) | Pass |
| G11 | index.html: gagal terus -> hanya 2 percobaan (tidak berulang tanpa batas), pesan galat jelas, tombol aktif | Pass |
| G12 | index.html: respons HTTP 500 BUKAN gangguan jaringan -> tidak diulang (1 request) dan pesan server tampil | Pass |
| G13 | pesan galat dari server berisi HTML di-escape (tidak jadi elemen) | Pass |
| G14 | dashboard: gangguan jaringan diulang satu kali lalu data tampil | Pass |
| G15 | tambah data: gagal jaringan -> toast galat dan tombol Simpan aktif lagi (finally) | Pass |
| G16 | tambah data (POST) TIDAK diulang otomatis (1 request) dan data tidak tersimpan | Pass |
| G17 | saat menyimpan: tombol nonaktif, label "Menyimpan...", spinner tampil | Pass |
| G18 | pengiriman ganda dicegah: hanya 1 request POST dan 1 baris data | Pass |
| G19 | ubah data (_method=PUT): gangguan jaringan diulang sekali lalu berhasil, data berubah | Pass |
| G20 | hapus: tombol konfirmasi berlabel "Menghapus..." dan nonaktif, lalu pulih | Pass |
| G21 | login: tombol bersatus loading dan gangguan jaringan diulang sekali lalu masuk (2 request) | Pass |

## Uji manual (untuk screenshot)
- Memasukkan nama `<b>Coba</b>` lewat form tambah, lalu melihat tabel `index.html` dan `dashboard.html`: tampil sebagai teks, bukan tebal.
- Throttling: DevTools > Network > Slow 3G, muat ulang dashboard: baris "Memuat data..." dengan spinner terlihat.
- Retry: DevTools > Network > centang Offline sebentar lalu hilangkan saat pesan "Koneksi bermasalah, mencoba lagi..." muncul.

## Catatan
- Percobaan ulang hanya untuk request yang aman diulang (GET, DELETE, ubah data `_method=PUT`, login). Tambah data (POST) tidak diulang agar tidak menimbulkan duplikat bila balasan server hilang.
- Respons HTTP 4xx/5xx bukan gangguan jaringan sehingga tidak diulang.
