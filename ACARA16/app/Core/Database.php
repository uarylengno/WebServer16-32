<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            try {
                self::$instance = new PDO(
                    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                    $config['user'],
                    $config['pass'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                Logger::error('Koneksi database gagal: ' . $e->getMessage());
                die('Terjadi kesalahan pada server. Silakan coba lagi nanti.');
            }
        }

        return self::$instance;
    }
}
