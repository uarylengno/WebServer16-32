<?php
// Acara 28, Langkah 2 + 4 + 5: menyusun paket siap unggah untuk cPanel di folder deploy_paket/.
//
//   php scripts/buat_paket_deploy.php --domain=https://contoh.ac.id --db=namacpanel_dbworkshop \
//        --dbuser=namacpanel_dbuser --dbpass=password_sesuai_cpanel [--out=deploy_paket]
//
//   Opsi tambahan (hosting tanpa folder di luar document root, mis. InfinityFree):
//        --layout=flat --dbhost=sqlXXX.infinityfree.com
//   Opsi admin tanpa SSH (membuat deploy_paket/buat_admin.sql berisi HASH, bukan password):
//        --adminuser=admin --adminemail=admin@domain.id --adminpass=PasswordKuat123
// Tanpa opsi, nilai placeholder dari modul dipakai (namacpanel_dbworkshop, namacpanel_dbuser, password_sesuai_cpanel)
// dan harus Anda ganti di server (Langkah 5). Hasil:
//   deploy_paket/app/                      -> unggah SEJAJAR dengan public_html (di luar jangkauan browser)
//   deploy_paket/public_html/              -> isi folder ini diunggah ke public_html
//   deploy_paket/db_workshop_hosting.sql   -> Import di phpMyAdmin cPanel (tanpa CREATE DATABASE/USE/akun seed)
//   deploy_paket/PETUNJUK_UPLOAD.txt
// Paket TIDAK memuat: foto uji, backup, docs, scripts, berkas Git, kredensial lokal.

if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Hanya dari CLI\n"); }

$root = dirname(__DIR__);
$opt = getopt('', ['domain::', 'db::', 'dbuser::', 'dbpass::', 'out::', 'tanpa-https', 'layout::', 'dbhost::', 'adminuser::', 'adminemail::', 'adminpass::']);
$flat = ($opt['layout'] ?? 'cpanel') === 'flat';   // flat = hosting yang tidak mengizinkan folder di luar document root (mis. InfinityFree: htdocs)
$dbhost = $opt['dbhost'] ?? 'localhost';
$https = str_starts_with($opt['domain'] ?? 'https://', 'https://') && !array_key_exists('tanpa-https', $opt);
$domain = rtrim($opt['domain'] ?? 'https://contoh-domain.ac.id', '/');
$db     = $opt['db']     ?? 'namacpanel_dbworkshop';
$dbuser = $opt['dbuser'] ?? 'namacpanel_dbuser';
$dbpass = $opt['dbpass'] ?? 'password_sesuai_cpanel';
$out    = $opt['out']    ?? $root . '/deploy_paket';
if (!preg_match('#^https?://[A-Za-z0-9.-]+(:\d+)?$#', $domain)) exit("--domain harus berbentuk https://domain (tanpa path)\n");

function hapusPohon(string $d): void {
    if (!is_dir($d)) return;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($d);
}
function salin(string $dari, string $ke, array $lewati = []): void {
    @mkdir($ke, 0755, true);
    foreach (new DirectoryIterator($dari) as $f) {
        if ($f->isDot() || in_array($f->getFilename(), $lewati, true)) continue;
        $tujuan = $ke . '/' . $f->getFilename();
        $f->isDir() ? salin($f->getPathname(), $tujuan, $lewati) : copy($f->getPathname(), $tujuan);
    }
}
function ganti(string $berkas, string $cari, string $pengganti, bool $wajib = true): void {
    $t = file_get_contents($berkas);
    $n = str_replace($cari, $pengganti, $t, $jumlah);
    if ($wajib && $jumlah === 0) exit("Gagal menyesuaikan " . basename($berkas) . ": teks '$cari' tidak ditemukan\n");
    file_put_contents($berkas, $n);
}

hapusPohon($out);
mkdir($out, 0755, true);

// ---- app/ ----
// app/.htaccess dan app/logs/.htaccess (Require all denied, Acara 30 Langkah 4) ikut tersalin; log lokal tidak ikut.
salin($root . '/app', $out . '/app', ['php_errors.log']);
if (!is_file($out . '/app/.htaccess')) exit("app/.htaccess tidak ditemukan di proyek\n");

// Langkah 5: kredensial basis data
$dbphp = $out . '/app/config/Database.php';
ganti($dbphp, "\$db   = 'db_workshop_acara30';", "\$db   = " . var_export($db, true) . ";");
ganti($dbphp, "\$host = 'localhost';", "\$host = " . var_export($dbhost, true) . ";");
ganti($dbphp, "\$user = 'root';        // hosting: namacpanel_dbuser", "\$user = " . var_export($dbuser, true) . ";");
ganti($dbphp, "\$pass = '';            // hosting: password_sesuai_cpanel", "\$pass = " . var_export($dbpass, true) . ";");

// Mode produksi
ganti($out . '/app/config/app.php', "getenv('APP_ENV') ?: 'development'", "getenv('APP_ENV') ?: 'production'");

// Kunci JWT acak khusus server ini (tidak pernah masuk Git; dipakai oleh Auth::secret())
$kunci = bin2hex(random_bytes(32));
file_put_contents($out . '/app/config/secret.local.php',
    "<?php\n// Dibuat otomatis oleh scripts/buat_paket_deploy.php. JANGAN dimasukkan ke Git atau dibagikan.\nreturn '$kunci';\n");

// ---- public_html/ ----
$pub = $out . '/public_html';
salin($root . '/public', $pub, ['uploads']);
mkdir($pub . '/uploads', 0755);
copy($root . '/public/uploads/.htaccess', $pub . '/uploads/.htaccess');

// CORS: View dan API satu origin pada hosting, sehingga wildcard tidak diperlukan.
ganti($pub . '/index.php', "header('Access-Control-Allow-Origin: *');",
    "// Produksi: View dan API satu origin, jadi header CORS dimatikan. Jika frontend lain perlu mengakses API,\n// ganti dengan origin tertentu, mis. header('Access-Control-Allow-Origin: https://frontend.contoh.ac.id');\n// header('Access-Control-Allow-Origin: *');");

// BASE_URL (Langkah 5)
foreach (['common.js', 'app.js'] as $js) {
    if (is_file("$pub/views/$js")) {
        $t = file_get_contents("$pub/views/$js");
        $t = preg_replace("#const BASE_URL_BAWAAN = '[^']*';#", "const BASE_URL_BAWAAN = '" . $domain . "';", $t, 1, $n);
        if ($n !== 1 && $js === 'common.js') exit("BASE_URL_BAWAAN tidak ditemukan di $js\n");
        file_put_contents("$pub/views/$js", $t);
    }
}

// .htaccess produksi
$ht = file_get_contents($pub . '/.htaccess');
if ($https) {
    // Acara 30 (Langkah 2): aktifkan pengalihan HTTPS (tiga baris yang dikomentari di public/.htaccess).
    $ht = preg_replace('/^# (RewriteCond %\{HTTPS\} off|RewriteCond %\{HTTP:X-Forwarded-Proto\} !https|RewriteRule \^\(\.\*\)\$ https:\/\/%\{HTTP_HOST\}%\{REQUEST_URI\} \[L,R=301\])$/m', '$1', $ht, -1, $nHttps);
    if ($nHttps !== 3) exit("Aturan pengalihan HTTPS tidak ditemukan di public/.htaccess\n");
}
file_put_contents($pub . '/.htaccess',
"# Acara 28/30: konfigurasi produksi\nOptions -Indexes\n\n"
. "# Berkas tersembunyi dan berkas konfigurasi tidak boleh diunduh.\n"
. "<FilesMatch \"^(\\..*|.*\\.(sql|log|ini|bak|md))\$\">\n    <IfModule mod_authz_core.c>\n        Require all denied\n    </IfModule>\n</FilesMatch>\n\n"
. "# Header keamanan dasar.\n<IfModule mod_headers.c>\n    Header always set X-Content-Type-Options \"nosniff\"\n    Header always set X-Frame-Options \"SAMEORIGIN\"\n    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"\n"
. "    # Aktifkan HSTS hanya setelah HTTPS berjalan stabil (browser mengingatnya selama setahun):\n    # Header always set Strict-Transport-Security \"max-age=31536000\" env=HTTPS\n</IfModule>\n\n"
. $ht);

// Batas unggah PHP untuk foto 2MB (PHP-FPM/LiteSpeed membaca .user.ini; php_value di .htaccess dapat memicu 500)
file_put_contents($pub . '/.user.ini', "upload_max_filesize = 3M\npost_max_size = 4M\ndisplay_errors = Off\nlog_errors = On\n"); // error_log diatur oleh app/config/app.php (app/logs/php_errors.log)

// ---- SQL hosting ----
$sql = file_get_contents($root . '/db_workshop_acara30.sql');
$sql = preg_replace('/^\s*CREATE DATABASE[^;]*;\s*$/mi', '', $sql);
$sql = preg_replace('/^\s*USE\s+[^;]*;\s*$/mi', '', $sql);
// buang INSERT akun seed (password publik): hanya blok INSERT INTO users ... ;
$sql = preg_replace('/(^--[^\n]*\n)*INSERT INTO `?users`?.*?;\s*/ms', "-- Akun awal (seed) sengaja tidak disertakan pada hosting: buat admin sendiri (docs/DEPLOYMENT.md).\n", $sql, -1, $nSeed);
$sql = preg_replace('/\A(--[^\n]*\n)+\s*/', '', $sql); // buang kepala komentar versi lokal
$kepala = "-- Acara 28: SQL untuk hosting cPanel.\n-- Dibuat dari db_workshop_acara30.sql tanpa CREATE DATABASE, USE, dan akun seed.\n"
        . "-- Pilih basis data cPanel Anda di phpMyAdmin, lalu tab Import. Admin dibuat dengan scripts/buat_admin.php atau SQL manual (lihat docs/DEPLOYMENT.md).\n"
        . "-- Peringatan: DROP TABLE di bawah menghapus tabel yang sudah ada.\n";
file_put_contents($out . '/db_workshop_hosting.sql', $kepala . $sql);

// ---- Admin tanpa SSH ----
if (isset($opt['adminuser'], $opt['adminemail'], $opt['adminpass'])) {
    if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $opt['adminuser']) || !filter_var($opt['adminemail'], FILTER_VALIDATE_EMAIL)
        || strlen($opt['adminpass']) < 8 || strlen($opt['adminpass']) > 72) exit("--adminuser/--adminemail/--adminpass tidak valid (password 8-72 karakter)\n");
    $hash = password_hash($opt['adminpass'], PASSWORD_BCRYPT);
    file_put_contents($out . '/buat_admin.sql', "-- Membuat akun admin (password disimpan sebagai hash bcrypt, bukan teks asli).\n-- Jalankan di phpMyAdmin hosting: pilih database > tab SQL. Hapus berkas ini dari laptop setelah dipakai.\n"
        . "INSERT INTO users (username, email, password, role) VALUES (" . "'" . $opt['adminuser'] . "', '" . str_replace("'", "''", $opt['adminemail']) . "', '" . $hash . "', 'admin');\n");
}

// ---- Tata letak flat (InfinityFree dan sejenisnya) ----
if ($flat) {
    // app/ ditaruh DI DALAM document root (htdocs/app). Pertahanan: app/.htaccess dan app/logs/.htaccess (Require all denied).
    rename($out . '/public_html', $out . '/htdocs');
    rename($out . '/app', $out . '/htdocs/app');
    ganti($out . '/htdocs/index.php', "__DIR__ . '/../app/core/autoload.php'", "__DIR__ . '/app/core/autoload.php'");
    ganti($out . '/htdocs/index.php', "__DIR__ . '/../app/config/app.php'", "__DIR__ . '/app/config/app.php'");
    $pub = $out . '/htdocs';
}

file_put_contents($out . '/PETUNJUK_UPLOAD.txt',
($flat ? "PAKET TATA LETAK FLAT (InfinityFree): unggah ISI folder htdocs/ (termasuk folder app/ dan berkas titik) ke folder htdocs di hosting. Ikuti docs/INFINITYFREE.md.\n\n" : "") . "PAKET DEPLOYMENT ACARA 28/30\n=========================\n"
. "Domain        : $domain\nDatabase      : $db\nUser database : $dbuser\nHost MySQL    : $dbhost\n\n"
. "1. cPanel > MySQL Databases: buat database, user, password; tambahkan user ke database dengan ALL PRIVILEGES.\n"
. "2. phpMyAdmin: pilih database tersebut > Import > db_workshop_hosting.sql.\n"
. "3. File Manager: unggah folder app/ ke /home/NAMACPANEL/app (SEJAJAR dengan public_html, bukan di dalamnya).\n"
. "4. Unggah ISI folder public_html/ (termasuk berkas titik .htaccess dan .user.ini: aktifkan Show Hidden Files) ke public_html.\n"
. "   Jika Document Root dapat diatur: unggah public_html/ sebagai folder public di sejajar app/ lalu arahkan Document Root ke sana.\n"
. "5. Atur izin: folder 755, berkas 644; public_html/uploads harus dapat ditulis (755, atau 775 bila foto gagal tersimpan).\n"
. "6. Buat akun admin: tab SQL di phpMyAdmin (lihat docs/DEPLOYMENT.md bagian 'Membuat admin') atau SSH: php scripts/buat_admin.php\n"
. "7. Uji: $domain/views/login.html ; $domain/mahasiswa (200 JSON) ; $domain/app/config/Database.php (harus 404/403).\n"
. "8. cPanel > SSL/TLS Status > Run AutoSSL; uji https://. Pengalihan HTTPS " . ($https ? "SUDAH aktif di public_html/.htaccess (jika https:// belum berfungsi, beri komentar # pada 3 baris RewriteCond/RewriteRule HTTPS sampai SSL terbit)" : "NONAKTIF (paket dibuat tanpa https); buka komentarnya setelah SSL terbit") . ".\n"
. "9. Verifikasi menyeluruh: php scripts/cek_produksi.php $domain (dari komputer Anda).\n");

// Acara 30 (Langkah 5): dokumentasi API dengan base URL produksi (salinan; berkas di proyek tidak diubah).
mkdir($out . '/docs', 0755);
$lokal = 'http://localhost/ACARA16-25/ACARA30/public';
$md = file_get_contents($root . '/docs/API_DOCUMENTATION.md');
$md = preg_replace('/^\| Base URL \(lokal\).*\R/m', '', $md);                      // baris lokal dibuang pada salinan produksi
$md = str_replace(['https://namadomain.com', $lokal], $domain, $md);                // base URL dan contoh curl memakai domain produksi
$md = str_replace(['| Base URL (produksi) |', ' (ganti dengan domain hosting; wajib HTTPS)'], ['| Base URL |', ' (HTTPS)'], $md);
file_put_contents($out . '/docs/API_DOCUMENTATION.md', $md);
$yaml = file_get_contents($root . '/docs/openapi.yaml');
$yaml = str_replace(["  - url: $lokal\n    description: Lokal (Laragon)\n", 'https://namadomain.com', 'Produksi (ganti dengan domain hosting Anda)'], ['', $domain, 'Produksi'], $yaml);
file_put_contents($out . '/docs/openapi.yaml', $yaml);

echo "Paket dibuat di: $out\n  domain=$domain db=$db user=$dbuser (seed users dibuang: " . ($nSeed ? 'ya' : 'tidak ada') . ")\n";
