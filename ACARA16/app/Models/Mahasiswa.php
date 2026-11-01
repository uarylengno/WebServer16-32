<?php

namespace App\Models;

class Mahasiswa
{
    private ?int $id;
    private string $nim;
    private string $nama;
    private string $email;
    private int $prodiId;
    private int $angkatan;

    // Field tambahan hasil JOIN, read-only (tidak perlu setter)
    private ?string $prodiNama;

    public function __construct(
        string $nim,
        string $nama,
        string $email = '',
        int $prodiId = 0,
        int $angkatan = 0,
        ?int $id = null,
        ?string $prodiNama = null
    ) {
        $this->id = $id;
        $this->setNim($nim);
        $this->setNama($nama);
        $this->email = $email;
        $this->prodiId = $prodiId;
        $this->angkatan = $angkatan;
        $this->prodiNama = $prodiNama;
    }

    // ---- Getter ----
    public function getId(): ?int { return $this->id; }
    public function getNim(): string { return $this->nim; }
    public function getNama(): string { return $this->nama; }
    public function getEmail(): string { return $this->email; }
    public function getProdiId(): int { return $this->prodiId; }
    public function getAngkatan(): int { return $this->angkatan; }
    public function getProdiNama(): ?string { return $this->prodiNama; }

    // ---- Setter dengan validasi ----
    public function setNim(string $nim): void
    {
        if (!ctype_digit($nim)) {
            throw new \InvalidArgumentException("NIM harus berupa angka.");
        }
        $this->nim = $nim;
    }

    public function setNama(string $nama): void
    {
        if (trim($nama) === '') {
            throw new \InvalidArgumentException("Nama mahasiswa tidak boleh kosong.");
        }
        $this->nama = $nama;
    }

    public function setEmail(string $email): void { $this->email = $email; }
    public function setProdiId(int $prodiId): void { $this->prodiId = $prodiId; }
    public function setAngkatan(int $angkatan): void { $this->angkatan = $angkatan; }
}
