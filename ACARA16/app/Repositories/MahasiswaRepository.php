<?php

namespace App\Repositories;

use PDO;
use App\Models\Mahasiswa;

class MahasiswaRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT m.*, p.nama AS prodi_nama
             FROM mahasiswa m
             LEFT JOIN prodi p ON p.id = m.prodi_id
             ORDER BY m.id DESC"
        );

        return array_map(fn($row) => $this->mapToEntity($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id)
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, p.nama AS prodi_nama
             FROM mahasiswa m
             LEFT JOIN prodi p ON p.id = m.prodi_id
             WHERE m.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToEntity($row) : null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO mahasiswa (nim, nama, email, prodi_id, angkatan)
             VALUES (:nim, :nama, :email, :prodi_id, :angkatan)"
        );
        $stmt->execute([
            'nim' => $data['nim'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'prodi_id' => $data['prodi_id'],
            'angkatan' => $data['angkatan'] ?? date('Y'),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare(
            "UPDATE mahasiswa
             SET nim = :nim, nama = :nama, email = :email, prodi_id = :prodi_id, angkatan = :angkatan
             WHERE id = :id"
        );
        $stmt->execute([
            'nim' => $data['nim'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'prodi_id' => $data['prodi_id'],
            'angkatan' => $data['angkatan'] ?? date('Y'),
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM mahasiswa WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function existsByNim(string $nim, ?int $ignoreId = null): bool
    {
        if ($ignoreId !== null) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim AND id != :id"
            );
            $stmt->execute(['nim' => $nim, 'id' => $ignoreId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim"
            );
            $stmt->execute(['nim' => $nim]);
        }

        return (int)$stmt->fetchColumn() > 0;
    }

    private function mapToEntity(array $row): Mahasiswa
    {
        return new Mahasiswa(
            nim: $row['nim'],
            nama: $row['nama'],
            email: $row['email'] ?? '',
            prodiId: (int)($row['prodi_id'] ?? 0),
            angkatan: (int)($row['angkatan'] ?? 0),
            id: (int)$row['id'],
            prodiNama: $row['prodi_nama'] ?? null
        );
    }
}
