<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\MatakuliahRepository;

class MatakuliahController extends Controller
{
    private MatakuliahRepository $repository;

    public function __construct(MatakuliahRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(): void
    {
        $matakuliah = $this->repository->all();
        $this->view('matakuliah/index', ['matakuliah' => $matakuliah]);
    }
}
