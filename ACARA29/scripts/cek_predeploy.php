<?php
// Acara 28, Langkah 1: checklist pra-deployment otomatis.
// Jalankan dari folder proyek:   php scripts/cek_predeploy.php
// Keluaran: daftar [OK] / [GAGAL] / [CEK] dan kode keluar 1 bila ada [GAGAL].
// Pemeriksaan "Pass semua test case" dan "tidak ada kredensial di luar Database.php" dilakukan di sini;
// pengujian blackbox itu sendiri tetap dijalankan manual di Postman (docs/TEST_CASE.md).

$root = dirname(__DIR__);
$gagal = 0;
function hasil(string $s, string $teks): void {
    global $gagal;
    if ($s === 'GAGAL') $gagal++;
    printf("[%-5s] %s\n", $s, $teks);
}

// 1. Test case Acara 23
$tc = $root . '/docs/TEST_CASE.md';
if (!is_file($tc)) {
    hasil('GAGAL', 'docs/TEST_CASE.md tidak ditemukan');
} else {
    // Status akhir = sel terakhir tiap baris "| TCnn | ...". "Fail, lalu Pass setelah retest" dihitung Pass.
    $total = $pass = 0;
    $belum = [];
    foreach (file($tc, FILE_IGNORE_NEW_LINES) as $baris) {
        if (!preg_match('/^\|\s*(TC\d+)\s*\|/', $baris, $m)) continue;
        $sel = array_map('trim', explode('|', trim($baris, " |")));
        $status = end($sel);
        $total++;
        if (preg_match('/Pass\s*(setelah retest)?\.?$/i', $status)) $pass++; else $belum[] = $m[1];
    }
    if ($total === 0) hasil('GAGAL', 'TEST_CASE.md: baris TCnn tidak ditemukan');
    elseif ($belum)   hasil('GAGAL', "TEST_CASE.md: $pass/$total Pass; belum Pass: " . implode(', ', $belum));
    else              hasil('OK', "TEST_CASE.md: semua test case berstatus akhir Pass ($pass/$total)");
}

// 2. Kredensial hardcoded di luar Database.php
$pola = [
    '/new\s+PDO\s*\(/i'                              => 'pembuatan koneksi PDO',
    '/mysqli_connect|new\s+mysqli\s*\(/i'            => 'koneksi mysqli',
    '/[\'"]password[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/i' => 'password literal pada array',
    '/(DB_PASS|DB_PASSWORD|db_pass)\s*[=:]\s*[\'"][^\'"]+/i' => 'variabel password database',
    '/mysql:\/\/[^\s\'"]*:[^\s\'"]*@/i'              => 'URL koneksi dengan password',
];
$dilewati = ['vendor', 'uploads', 'docs', 'scripts', 'backup', 'contoh_uji', '.git'];
$temuan = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
    if (!preg_match('/\.(php|js|html|json|env|ini)$/i', $rel)) continue;
    if (preg_match('#^(' . implode('|', $dilewati) . ')/#', $rel)) continue;
    if (preg_match('/postman_(collection|environment)\.json$/', $rel)) continue;
    if ($rel === 'app/config/Database.php') continue;
    $teks = file_get_contents($f->getPathname());
    foreach ($pola as $re => $ket) {
        if (preg_match($re, $teks)) $temuan[] = "$rel: $ket";
    }
}
$temuan ? hasil('GAGAL', "Kredensial/koneksi di luar Database.php:\n         - " . implode("\n         - ", $temuan))
        : hasil('OK', 'Tidak ada kredensial database di luar app/config/Database.php');

// 3. Kunci JWT
$auth = file_get_contents($root . '/app/core/Auth.php');
if (is_file($root . '/app/config/secret.local.php')) {
    hasil('OK', 'app/config/secret.local.php ada (kunci JWT khusus server)');
} elseif (getenv('JWT_SECRET')) {
    hasil('OK', 'JWT_SECRET terpasang sebagai environment variable');
} else {
    hasil('CEK', 'Kunci JWT masih bawaan (Auth::SECRET). Di server buat app/config/secret.local.php (php scripts/buat_paket_deploy.php melakukannya otomatis)');
}
if (preg_match("/const SECRET\s*=\s*'([^']+)'/", $auth, $m)) {
    hasil('CEK', "Auth::SECRET bawaan '" . $m[1] . "' ada di kode; aman hanya jika secret.local.php dipakai pada produksi");
}

// 4. Akun seed bawaan
$sql = @file_get_contents($root . '/db_workshop_acara29.sql') ?: '';
if (stripos($sql, 'Admin@12345') !== false || preg_match('/INSERT INTO `?users`?/i', $sql)) {
    hasil('CEK', 'SQL lokal memuat akun seed (admin/user_demo) berpassword publik. Berkas SQL hosting TIDAK memuatnya; buat admin dengan scripts/buat_admin.php');
}

// 5. Mode aplikasi, CORS, BASE_URL
$app = file_get_contents($root . '/app/config/app.php');
hasil('CEK', preg_match("/getenv\('APP_ENV'\)\s*\?:\s*'production'/", $app)
    ? 'APP_ENV bawaan = production' : "APP_ENV bawaan = development (paket hosting mengubahnya ke production)");
$idx = file_get_contents($root . '/public/index.php');
hasil('CEK', preg_match('/^header\(\'Access-Control-Allow-Origin: \*\'\)/m', $idx)
    ? "CORS masih '*' (paket hosting menonaktifkannya karena View dan API satu origin)" : 'CORS tidak memakai wildcard');
foreach (['public/views/common.js', 'public/views/app.js'] as $js) {
    if (is_file("$root/$js") && preg_match("/BASE_URL_BAWAAN\s*=\s*'(http:\/\/localhost[^']*)'/", file_get_contents("$root/$js"), $m)) {
        hasil('CEK', "$js: BASE_URL_BAWAAN mengarah ke localhost ($m[1]); dipakai hanya bila halaman dibuka lewat file://");
    }
}

// 6. Folder uploads dan berkas yang tidak boleh ikut
$dev = glob($root . '/public/uploads/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [];
hasil($dev ? 'CEK' : 'OK', $dev ? count($dev) . ' foto uji ada di public/uploads (paket hosting tidak menyertakannya)' : 'public/uploads bersih dari foto uji');
hasil(is_file($root . '/public/uploads/.htaccess') ? 'OK' : 'GAGAL', 'public/uploads/.htaccess (blokir eksekusi skrip) ' . (is_file($root . '/public/uploads/.htaccess') ? 'ada' : 'TIDAK ada'));
hasil(is_file($root . '/public/.htaccess') ? 'OK' : 'GAGAL', 'public/.htaccess (Front Controller) ' . (is_file($root . '/public/.htaccess') ? 'ada' : 'TIDAK ada'));

// 7. Versi PHP dan ekstensi
hasil(version_compare(PHP_VERSION, '8.0.0', '>=') ? 'OK' : 'GAGAL', 'PHP ' . PHP_VERSION . ' (butuh >= 8.0)');
foreach (['pdo_mysql', 'exif', 'mbstring', 'fileinfo'] as $e) {
    hasil(extension_loaded($e) ? 'OK' : 'CEK', "ekstensi $e " . (extension_loaded($e) ? 'aktif' : 'tidak aktif di PHP lokal ini; pastikan aktif di cPanel (Select PHP Version)'));
}

echo $gagal ? "\n$gagal pemeriksaan GAGAL. Perbaiki sebelum deployment.\n" : "\nTidak ada pemeriksaan GAGAL. Baris [CEK] perlu dibaca dan dipahami.\n";
exit($gagal ? 1 : 0);
