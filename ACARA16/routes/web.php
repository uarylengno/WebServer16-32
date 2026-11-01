<?php

use App\Core\Database;
use App\Repositories\MahasiswaRepository;
use App\Repositories\ProdiRepository;
use App\Repositories\MatakuliahRepository;
use App\Services\MahasiswaService;
use App\Controllers\MahasiswaController;
use App\Controllers\ProdiController;
use App\Controllers\MatakuliahController;

$db = Database::getInstance();

$mahasiswaRepository = new MahasiswaRepository($db);
$prodiRepository = new ProdiRepository($db);
$matakuliahRepository = new MatakuliahRepository($db);

$mahasiswaService = new MahasiswaService($mahasiswaRepository, $prodiRepository);

$mahasiswaController = new MahasiswaController($mahasiswaService);
$prodiController = new ProdiController($prodiRepository);
$matakuliahController = new MatakuliahController($matakuliahRepository);

// Halaman utama arahkan ke daftar mahasiswa
$router->get('/', [$mahasiswaController, 'index']);

// Mahasiswa
$router->get('/mahasiswa', [$mahasiswaController, 'index']);
$router->get('/mahasiswa/create', [$mahasiswaController, 'create']);
$router->post('/mahasiswa/store', [$mahasiswaController, 'store']);
$router->get('/mahasiswa/{id}/edit', [$mahasiswaController, 'edit']);
$router->post('/mahasiswa/{id}/update', [$mahasiswaController, 'update']);
$router->post('/mahasiswa/{id}/delete', [$mahasiswaController, 'destroy']);

// Prodi
$router->get('/prodi', [$prodiController, 'index']);

// Mata Kuliah
$router->get('/matakuliah', [$matakuliahController, 'index']);
