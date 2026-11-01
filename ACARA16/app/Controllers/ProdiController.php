<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\ProdiRepository;

class ProdiController extends Controller
{
    private ProdiRepository $repository;

    public function __construct(ProdiRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(): void
    {
        $prodi = $this->repository->all();
        $this->view('prodi/index', ['prodi' => $prodi]);
    }
}
