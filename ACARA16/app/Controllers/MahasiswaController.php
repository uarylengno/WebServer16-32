<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MahasiswaService;

class MahasiswaController extends Controller
{
    private MahasiswaService $service;

    public function __construct(MahasiswaService $service)
    {
        $this->service = $service;
    }

    public function index(): void
    {
        $keyword = trim($_GET['keyword'] ?? '');
        $mahasiswa = $this->service->list();

        if ($keyword !== '') {
            $mahasiswa = array_values(array_filter($mahasiswa, function ($m) use ($keyword) {
                return stripos($m->getNim(), $keyword) !== false
                    || stripos($m->getNama(), $keyword) !== false;
            }));
        }

        $this->view('mahasiswa/index', [
            'mahasiswa' => $mahasiswa,
            'keyword' => $keyword,
        ]);
    }

    public function create(): void
    {
        $prodi = $this->service->prodiOptions();
        $this->view('mahasiswa/create', ['prodi' => $prodi]);
    }

    public function store(): void
    {
        $result = $this->service->create($_POST);

        $this->flash($result['success'] ? 'success' : 'danger', $result['message']);
        $this->redirect('/mahasiswa'); // PRG: redirect setelah POST
    }

    public function edit($id): void
    {
        $mahasiswa = $this->service->find((int)$id);

        if (!$mahasiswa) {
            $this->flash('danger', 'Data mahasiswa tidak ditemukan.');
            $this->redirect('/mahasiswa');
            return;
        }

        $prodi = $this->service->prodiOptions();
        $this->view('mahasiswa/edit', ['mahasiswa' => $mahasiswa, 'prodi' => $prodi]);
    }

    public function update($id): void
    {
        $result = $this->service->update((int)$id, $_POST);

        $this->flash($result['success'] ? 'success' : 'danger', $result['message']);
        $this->redirect('/mahasiswa'); // PRG
    }

    public function destroy($id): void
    {
        $result = $this->service->delete((int)$id);

        $this->flash($result['success'] ? 'success' : 'danger', $result['message']);
        $this->redirect('/mahasiswa'); // PRG
    }
}
