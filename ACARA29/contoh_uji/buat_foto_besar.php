<?php
// Membuat foto_besar.png (> 2MB, gambar PNG yang valid) untuk menguji penolakan "Ukuran berkas maksimal 2MB".
// Jalankan dari folder contoh_uji:   php buat_foto_besar.php
// Butuh ekstensi GD (aktif secara bawaan di XAMPP dan Laragon).
if (!function_exists('imagecreatetruecolor')) { exit("Ekstensi GD tidak aktif.\n"); }
$lebar = 1100; $tinggi = 1100;
$img = imagecreatetruecolor($lebar, $tinggi);
for ($y = 0; $y < $tinggi; $y++) {          // piksel acak => PNG sulit dikompres => ukuran berkas besar
    for ($x = 0; $x < $lebar; $x++) {
        imagesetpixel($img, $x, $y, imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
    }
}
imagepng($img, __DIR__ . '/foto_besar.png', 1);
printf("foto_besar.png dibuat: %.2f MB\n", filesize(__DIR__ . '/foto_besar.png') / 1048576);
