<?php
class Mahasiswa {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        return $this->db->query('SELECT * FROM mahasiswa ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id) {
        $stmt = $this->db->prepare('SELECT * FROM mahasiswa WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Tugas mandiri no. 2: cek apakah NIM sudah dipakai.
     * $exceptId dipakai saat UPDATE agar NIM milik data itu sendiri tidak dianggap duplikat.
     */
    public function nimExists(string $nim, ?int $exceptId = null): bool {
        if ($exceptId === null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim');
            $stmt->execute([':nim' => $nim]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim AND id <> :id');
            $stmt->execute([':nim' => $nim, ':id' => $exceptId]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): string {
        $stmt = $this->db->prepare(
            'INSERT INTO mahasiswa (nim, nama, prodi) VALUES (:nim, :nama, :prodi)'
        );
        $stmt->execute([':nim' => $data['nim'], ':nama' => $data['nama'], ':prodi' => $data['prodi']]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare(
            'UPDATE mahasiswa SET nim = :nim, nama = :nama, prodi = :prodi WHERE id = :id'
        );
        $stmt->execute([':nim' => $data['nim'], ':nama' => $data['nama'], ':prodi' => $data['prodi'], ':id' => $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM mahasiswa WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
