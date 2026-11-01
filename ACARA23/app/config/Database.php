<?php
class Database {
    private static ?PDO $connection = null;

    public static function getConnection(): PDO {
        if (self::$connection === null) {
            $host = 'localhost';
            $db   = 'db_workshop';
            $charset = 'utf8mb4';
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            try {
                // Sesuaikan user/password jika MySQL kamu memakai password.
                self::$connection = new PDO($dsn, 'root', '', $options);
            } catch (PDOException $e) {
                error_log('Koneksi database gagal: ' . $e->getMessage());
                Response::json(500, false, 'Koneksi database gagal');
            }
        }
        return self::$connection;
    }
}
