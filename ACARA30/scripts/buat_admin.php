<?php
// Acara 28: membuat (atau mengganti password) akun admin pada database yang dikonfigurasi di Database.php.
// Dipakai di hosting sebagai pengganti akun seed berpassword publik.
//   php scripts/buat_admin.php <username> <email> <password>
// Tanpa akses SSH: unggah sementara, buka lewat browser TIDAK didukung (skrip menolak berjalan selain dari CLI).
// Alternatif tanpa SSH: phpMyAdmin > tab SQL > INSERT dengan hash dari `php -r "echo password_hash('...', PASSWORD_BCRYPT);"`.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("Hanya dari CLI\n"); }
if ($argc < 4) { exit("Pemakaian: php scripts/buat_admin.php <username> <email> <password>\n"); }
[, $u, $e, $p] = $argv;
if (!preg_match('/^[A-Za-z0-9_.]{3,50}$/', $u)) exit("Username 3-50 karakter: huruf, angka, _ dan .\n");
if (!filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($e) > 100) exit("Email tidak valid\n");
if (strlen($p) < 8 || strlen($p) > 72) exit("Password 8-72 karakter\n");

require_once __DIR__ . '/../app/core/autoload.php';
$db = Database::getConnection();
$hash = password_hash($p, PASSWORD_BCRYPT);
$cek = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$cek->execute([$u, $e]);
if ($row = $cek->fetch()) {
    $db->prepare("UPDATE users SET username=?, email=?, password=?, role='admin' WHERE id=?")->execute([$u, $e, $hash, $row['id']]);
    echo "Akun '$u' diperbarui menjadi admin dengan password baru.\n";
} else {
    $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')")->execute([$u, $e, $hash]);
    echo "Admin '$u' dibuat.\n";
}
