<?php
// Acara 30 (Langkah 5): pemeriksaan keamanan otomatis terhadap situs yang SUDAH berjalan (domain produksi atau lokal).
//   php scripts/cek_produksi.php https://namadomain.com
//   php scripts/cek_produksi.php http://127.0.0.1:8099        (uji lokal: pemeriksaan HTTPS dilewati)
// Hanya membaca (GET, dan satu POST login salah / satu DELETE ber-token palsu). Tidak mengubah data.
// Hasil: [OK] / [GAGAL] / [CEK]; kode keluar 1 bila ada [GAGAL]. Pengujian blackbox lengkap tetap di Postman (docs/TEST_CASE.md).

if ($argc < 2 || !preg_match('#^https?://[A-Za-z0-9.-]+(:\d+)?(/[^\s]*)?$#', $argv[1])) {
    exit("Pemakaian: php scripts/cek_produksi.php https://namadomain.com\n");
}
$base = rtrim($argv[1], '/');
$gagal = 0;
function hasil(string $s, string $t): void {
    global $gagal;
    if ($s === 'GAGAL') $gagal++;
    printf("[%-5s] %s\n", $s, $t);
}
/** Mengembalikan [status, header(huruf kecil => nilai), body]. Tidak mengikuti pengalihan. */
function minta(string $url, string $metode = 'GET', array $header = [], ?string $body = null): array {
    $ctx = stream_context_create(['http' => [
        'method' => $metode, 'header' => implode("\r\n", $header), 'content' => $body ?? '',
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 15,
    ]]);
    $isi = @file_get_contents($url, false, $ctx);
    if ($isi === false && empty($http_response_header)) return [0, [], ''];
    $status = 0; $h = [];
    foreach ($http_response_header ?? [] as $baris) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $baris, $m)) { $status = (int)$m[1]; $h = []; continue; }
        if (str_contains($baris, ':')) { [$k, $v] = explode(':', $baris, 2); $h[strtolower(trim($k))] = trim($v); }
    }
    return [$status, $h, (string)$isi];
}
$bocor = '/(<\?php|Fatal error|Warning:|Notice:|Deprecated:|Stack trace|PDOException|SQLSTATE|on line \d+|\/home\/|C:\\\\)/i';

[$st] = minta($base . '/mahasiswa');
if ($st === 0) exit("Tidak dapat menghubungi $base. Periksa alamat dan koneksi.\n");

// 1. HTTPS
$https = str_starts_with($base, 'https://');
if ($https) {
    hasil('OK', 'Alamat memakai HTTPS');
    $http = 'http://' . substr($base, 8);
    [$s, $h] = minta($http . '/mahasiswa');
    $lok = $h['location'] ?? '';
    (in_array($s, [301, 302, 307, 308], true) && str_starts_with($lok, 'https://'))
        ? hasil('OK', "http:// dialihkan ke HTTPS ($s -> $lok)")
        : hasil('GAGAL', "http:// TIDAK dialihkan ke HTTPS (status $s). Aktifkan Force HTTPS Redirect / aturan RewriteRule.");
} else {
    hasil('CEK', 'Alamat memakai http:// (wajar untuk uji lokal; produksi wajib HTTPS, pemeriksaan pengalihan dilewati)');
}

// 2. API dasar
[$s, $h, $b] = minta($base . '/mahasiswa');
$j = json_decode($b, true);
($s === 200 && is_array($j) && ($j['success'] ?? null) === true)
    ? hasil('OK', 'GET /mahasiswa -> 200 JSON')
    : hasil('GAGAL', "GET /mahasiswa -> status $s, respons bukan JSON sukses");

// 3. Berkas internal tidak boleh terbuka
foreach (['/app/config/Database.php', '/app/config/secret.local.php', '/app/config/app.php', '/app/logs/php_errors.log',
          '/db_workshop_hosting.sql', '/.htaccess', '/.user.ini', '/docs/API_DOCUMENTATION.md'] as $path) {
    [$s, , $b] = minta($base . $path);
    $jsonRouter = $s === 200 && str_contains($b, '"success"');   // 200 JSON dari Router berarti bukan isi berkas aslinya
    if (in_array($s, [403, 404], true)) hasil('OK', "$path -> $s");
    elseif ($jsonRouter) hasil('OK', "$path -> 200 tetapi JSON dari Router (bukan isi berkas)");
    elseif ($s === 200) hasil('GAGAL', "$path TERBACA dari browser (status 200)");
    else hasil('CEK', "$path -> status $s (pastikan isi berkas tidak terbaca)");
}

// 4. Daftar isi folder dan eksekusi skrip pada uploads
foreach (['/uploads/', '/views/', '/views/vendor/'] as $path) {
    [$s, , $b] = minta($base . $path);
    (stripos($b, 'Index of') !== false || stripos($b, 'Parent Directory') !== false)
        ? hasil('GAGAL', "$path menampilkan daftar isi folder (Options -Indexes belum aktif)")
        : hasil('OK', "$path tidak menampilkan daftar isi (status $s)");
}
[$s, , $b] = minta($base . '/uploads/uji.php');
(in_array($s, [403, 404], true) || str_contains($b, '"success"')) ? hasil('OK', "/uploads/uji.php -> $s") : hasil('GAGAL', "/uploads/uji.php -> status $s");

// 5. Error PHP tidak bocor
$uji = [
    ['GET', '/tidak-ada-resource', null], ['GET', '/mahasiswa?id=abc', null], ['GET', "/mahasiswa?id='", null],
    ['POST', '/login', '{rusak'], ['POST', '/login', '{"username":["x"],"password":{}}'], ['DELETE', '/mahasiswa', null],
];
$bocorAda = [];
foreach ($uji as [$m, $p, $isi]) {
    [$s, , $b] = minta($base . $p, $m, $isi ? ['Content-Type: application/json'] : [], $isi);
    if (preg_match($bocor, $b)) $bocorAda[] = "$m $p (status $s)";
}
$bocorAda ? hasil('GAGAL', "Respons memuat jejak error PHP/path server:\n         - " . implode("\n         - ", $bocorAda))
          : hasil('OK', 'Request tidak lazim (' . count($uji) . ' kasus) tidak membocorkan error PHP, path server, atau SQL');

// 6. Token yang ditandatangani kunci bawaan harus ditolak
$b64 = fn($x) => rtrim(strtr(base64_encode($x), '+/', '-_'), '=');
$hd = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
$pl = $b64(json_encode(['id' => 1, 'role' => 'admin', 'iat' => time(), 'exp' => time() + 300]));
$palsu = "$hd.$pl." . $b64(hash_hmac('sha256', "$hd.$pl", 'kunci_rahasia_workshop_2026', true));
[$s] = minta($base . '/mahasiswa?id=0', 'DELETE', ['Authorization: Bearer ' . $palsu]);
$s === 401 ? hasil('OK', 'Token bertanda tangan kunci bawaan ditolak (401): kunci JWT khusus server aktif')
           : hasil('GAGAL', "Token palsu berkunci bawaan TIDAK ditolak (status $s). Buat app/config/secret.local.php.");

// 7. Header
[, $h] = minta($base . '/mahasiswa');
$acao = $h['access-control-allow-origin'] ?? null;
$acao === '*' ? hasil('CEK', "CORS '*' aktif (paket hosting menonaktifkannya karena View dan API satu origin)")
              : hasil('OK', 'CORS tidak memakai wildcard' . ($acao ? " ($acao)" : ' (header tidak dikirim)'));
foreach (['x-content-type-options' => 'nosniff', 'x-frame-options' => null, 'referrer-policy' => null] as $k => $nilai) {
    isset($h[$k]) ? hasil('OK', "Header $k: {$h[$k]}") : hasil('CEK', "Header $k tidak ada (aktif di paket hosting lewat .htaccess/mod_headers)");
}
if ($https) {
    isset($h['strict-transport-security']) ? hasil('OK', 'HSTS aktif') : hasil('CEK', 'HSTS belum aktif (opsional; aktifkan setelah HTTPS stabil)');
}
isset($h['x-powered-by']) ? hasil('CEK', "Header X-Powered-By: {$h['x-powered-by']} membuka versi PHP (expose_php = Off)") : hasil('OK', 'X-Powered-By tidak dikirim');

echo $gagal ? "\n$gagal pemeriksaan GAGAL.\n" : "\nTidak ada pemeriksaan GAGAL. Baris [CEK] perlu dibaca.\n";
exit($gagal ? 1 : 0);
