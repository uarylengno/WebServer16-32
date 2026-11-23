<?php
// Tugas mandiri no. 1: resource kedua dengan pola MVC yang sama seperti Mahasiswa.
class Buku {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAll(): array {
        return $this->db->query('SELECT * FROM buku ORDER BY id DESC')->fetchAll();
    }

    public function find(int $id) {
        $stmt = $this->db->prepare('SELECT * FROM buku WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data): string {
        $stmt = $this->db->prepare(
            'INSERT INTO buku (judul, penulis, tahun) VALUES (:judul, :penulis, :tahun)'
        );
        $stmt->execute([':judul' => $data['judul'], ':penulis' => $data['penulis'], ':tahun' => (int) $data['tahun']]);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare(
            'UPDATE buku SET judul = :judul, penulis = :penulis, tahun = :tahun WHERE id = :id'
        );
        $stmt->execute([':judul' => $data['judul'], ':penulis' => $data['penulis'], ':tahun' => (int) $data['tahun'], ':id' => $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare('DELETE FROM buku WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
