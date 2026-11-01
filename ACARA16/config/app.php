<?php

// Sesuaikan ini kalau nama folder project kamu di htdocs berbeda
define('BASE_URL', '/BKPM-Web-Server/ACARA16/public');

// Autoload sederhana: memetakan namespace App\ ke folder app/
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
