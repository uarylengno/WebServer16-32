Berkas contoh untuk menguji upload foto (Acara 26)

foto_valid.jpg       JPG sah, kecil          -> upload diterima (201 / 200)
foto_baru.png        PNG sah, kecil          -> untuk mengganti foto (update)
skrip_palsu.jpg      berisi teks PHP, bernama .jpg -> HARUS ditolak 422 (isi bukan gambar)
buat_foto_besar.php  membuat foto_besar.png (> 2MB) -> HARUS ditolak 422 (ukuran)

Membuat foto besar:   cd contoh_uji   lalu   php buat_foto_besar.php
(Di Laragon: menu Laragon > Terminal, atau pakai php.exe dari XAMPP.)

Skrip PHP palsu di atas hanya satu baris teks tak berbahaya; fungsinya hanya menguji bahwa server menolak
berkas yang ekstensinya .jpg tetapi isinya bukan gambar.
