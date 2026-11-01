<?php

namespace App\Repositories;

use PDO;

class MatakuliahRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM matakuliah ORDER BY nama ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Kalau tabel matakuliah belum ada / strukturnya beda, jangan sampai aplikasi crash
            return [];
        }
    }
}
