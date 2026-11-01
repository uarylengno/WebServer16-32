<?php

namespace App\Repositories;

use PDO;

class ProdiRepository
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM prodi ORDER BY nama ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dipakai Service untuk memeriksa "program studi tersedia"
    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM prodi WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
