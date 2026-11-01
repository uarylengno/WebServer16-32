<?php

namespace App\Services;

use App\Repositories\MahasiswaRepository;
use App\Repositories\ProdiRepository;
use App\Core\Logger;

class MahasiswaService
{
    private MahasiswaRepository $mahasiswaRepository;
    private ProdiRepository $prodiRepository;

    public function __construct(
        MahasiswaRepository $mahasiswaRepository,
        ProdiRepository $prodiRepository
    ) {
        $this->mahasiswaRepository = $mahasiswaRepository;
        $this->prodiRepository = $prodiRepository;
    }

    public function list(): array
    {
        try {
            return $this->mahasiswaRepository->all();
        } catch (\Throwable $e) {
            Logger::error('Gagal mengambil data mahasiswa: ' . $e->getMessage());
            return [];
        }
    }

    public function find(int $id)
    {
        try {
            return $this->mahasiswaRepository->find($id);
        } catch (\Throwable $e) {
            Logger::error('Gagal mengambil mahasiswa id=' . $id . ': ' . $e->getMessage());
            return null;
        }
    }

    public function prodiOptions(): array
    {
        try {
            return $this->prodiRepository->all();
        } catch (\Throwable $e) {
            Logger::error('Gagal mengambil data prodi: ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $input): array
    {
        $errors = $this->validate($input);

        if (!empty($errors)) {
            return ['success' => false, 'message' => $errors[0]];
        }

        try {
            $this->mahasiswaRepository->create($input);
            return ['success' => true, 'message' => 'Data mahasiswa berhasil ditambahkan.'];
        } catch (\Throwable $e) {
            Logger::error('Gagal menambah mahasiswa: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Data gagal disimpan.'];
        }
    }

    public function update(int $id, array $input): array
    {
        $errors = $this->validate($input, $id);

        if (!empty($errors)) {
            return ['success' => false, 'message' => $errors[0]];
        }

        try {
            $this->mahasiswaRepository->update($id, $input);
            return ['success' => true, 'message' => 'Data mahasiswa berhasil diubah.'];
        } catch (\Throwable $e) {
            Logger::error('Gagal mengubah mahasiswa id=' . $id . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'Data gagal disimpan.'];
        }
    }

    public function delete(int $id): array
    {
        try {
            $this->mahasiswaRepository->delete($id);
            return ['success' => true, 'message' => 'Data mahasiswa berhasil dihapus.'];
        } catch (\Throwable $e) {
            Logger::error('Gagal menghapus mahasiswa id=' . $id . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'Data gagal dihapus.'];
        }
    }

    private function validate(array $input, ?int $ignoreId = null): array
    {
        $errors = [];

        $nim = trim($input['nim'] ?? '');
        $nama = trim($input['nama'] ?? '');
        $email = trim($input['email'] ?? '');
        $prodiId = (int)($input['prodi_id'] ?? 0);

        if ($nim === '') {
            $errors[] = 'NIM wajib diisi.';
        } elseif (!ctype_digit($nim)) {
            $errors[] = 'NIM harus berupa angka.';
        } elseif ($this->mahasiswaRepository->existsByNim($nim, $ignoreId)) {
            $errors[] = 'NIM sudah terdaftar.';
        }

        if ($nama === '') {
            $errors[] = 'Nama wajib diisi.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        if ($prodiId <= 0 || !$this->prodiRepository->exists($prodiId)) {
            $errors[] = 'Program studi tidak valid.';
        }

        return $errors;
    }
}
