<?php
/**
 * Model Mahasiswa. Acara 20: setiap query dibungkus try...catch (PDOException).
 * Detail error (pesan SQL) hanya masuk ke log server; client hanya menerima pesan umum 500.
 */
class Mahasiswa {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /** Catat detail error ke log server, lalu balas 500 generik tanpa membocorkan SQL/nama tabel. */
    private function fail(PDOException $e): void {
        error_log('[Mahasiswa] ' . $e->getMessage());
        Response::json(500, false, 'Terjadi kesalahan pada server');
    }

    public function getAll(): array {
        try {
            return $this->db->query('SELECT * FROM mahasiswa ORDER BY id DESC')->fetchAll();
        } catch (PDOException $e) {
            $this->fail($e);
        }
    }

    public function find(int $id) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM mahasiswa WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->fail($e);
        }
    }

    /**
     * Cek apakah NIM sudah dipakai.
     * $exceptId dipakai saat UPDATE agar NIM milik data itu sendiri tidak dianggap duplikat.
     */
    public function nimExists(string $nim, ?int $exceptId = null): bool {
        try {
            if ($exceptId === null) {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim');
                $stmt->execute([':nim' => $nim]);
            } else {
                $stmt = $this->db->prepare('SELECT COUNT(*) FROM mahasiswa WHERE nim = :nim AND id <> :id');
                $stmt->execute([':nim' => $nim, ':id' => $exceptId]);
            }
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->fail($e);
        }
    }

    public function create(array $data): string {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO mahasiswa (nim, nama, prodi) VALUES (:nim, :nama, :prodi)'
            );
            $stmt->execute([':nim' => $data['nim'], ':nama' => $data['nama'], ':prodi' => $data['prodi']]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // 23000 = pelanggaran UNIQUE (dua request dengan NIM sama masuk bersamaan)
            if ($e->getCode() === '23000') {
                Response::json(409, false, 'NIM sudah terdaftar', null, ['nim' => 'NIM sudah dipakai mahasiswa lain']);
            }
            $this->fail($e);
        }
    }

    public function update(int $id, array $data): void {
        try {
            $stmt = $this->db->prepare(
                'UPDATE mahasiswa SET nim = :nim, nama = :nama, prodi = :prodi WHERE id = :id'
            );
            $stmt->execute([':nim' => $data['nim'], ':nama' => $data['nama'], ':prodi' => $data['prodi'], ':id' => $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::json(409, false, 'NIM sudah dipakai mahasiswa lain', null, ['nim' => 'NIM sudah dipakai mahasiswa lain']);
            }
            $this->fail($e);
        }
    }

    public function delete(int $id): void {
        try {
            $stmt = $this->db->prepare('DELETE FROM mahasiswa WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            $this->fail($e);
        }
    }
}
