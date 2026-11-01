<?php

require __DIR__ . '/../../config/app.php';

use App\Core\Database;
use App\Repositories\MahasiswaRepository;
use App\Repositories\ProdiRepository;
use App\Services\MahasiswaService;

header('Content-Type: application/json');

function sendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function mahasiswaToArray($m): array
{
    return [
        'id' => $m->getId(),
        'nim' => $m->getNim(),
        'nama' => $m->getNama(),
        'email' => $m->getEmail(),
        'prodi_id' => $m->getProdiId(),
        'angkatan' => $m->getAngkatan(),
    ];
}

function handleGet(MahasiswaService $service): void
{
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id !== null) {
        $mhs = $service->find($id);

        if (!$mhs) {
            sendJson(404, [
                'success' => false,
                'message' => 'Data mahasiswa tidak ditemukan',
            ]);
        }

        sendJson(200, [
            'success' => true,
            'message' => 'Data berhasil diambil',
            'data' => mahasiswaToArray($mhs),
        ]);
    }

    $list = $service->list();
    $data = array_map('mahasiswaToArray', $list);

    sendJson(200, [
        'success' => true,
        'message' => 'Data berhasil diambil',
        'data' => $data,
    ]);
}

function handlePost(MahasiswaService $service): void
{
    $input = readJsonBody();

    if (empty($input)) {
        sendJson(400, [
            'success' => false,
            'message' => 'Data JSON tidak lengkap',
        ]);
    }

    $result = $service->create($input);

    sendJson($result['success'] ? 201 : 400, [
        'success' => $result['success'],
        'message' => $result['message'],
    ]);
}

function handlePut(MahasiswaService $service): void
{
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id === null) {
        sendJson(400, [
            'success' => false,
            'message' => 'ID mahasiswa wajib disertakan di URL',
        ]);
    }

    $existing = $service->find($id);
    if (!$existing) {
        sendJson(404, [
            'success' => false,
            'message' => 'Data mahasiswa tidak ditemukan',
        ]);
    }

    $input = readJsonBody();

    if (empty($input)) {
        sendJson(400, [
            'success' => false,
            'message' => 'Data JSON tidak lengkap',
        ]);
    }

    $result = $service->update($id, $input);

    sendJson($result['success'] ? 200 : 400, [
        'success' => $result['success'],
        'message' => $result['message'],
    ]);
}

function handleDelete(MahasiswaService $service): void
{
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($id === null) {
        sendJson(400, [
            'success' => false,
            'message' => 'ID mahasiswa wajib disertakan di URL',
        ]);
    }

    $existing = $service->find($id);
    if (!$existing) {
        sendJson(404, [
            'success' => false,
            'message' => 'Data mahasiswa tidak ditemukan',
        ]);
    }

    $result = $service->delete($id);

    sendJson($result['success'] ? 200 : 400, [
        'success' => $result['success'],
        'message' => $result['message'],
    ]);
}

try {
    $db = Database::getInstance();
    $service = new MahasiswaService(
        new MahasiswaRepository($db),
        new ProdiRepository($db)
    );

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            handleGet($service);
            break;
        case 'POST':
            handlePost($service);
            break;
        case 'PUT':
            handlePut($service);
            break;
        case 'DELETE':
            handleDelete($service);
            break;
        default:
            sendJson(405, [
                'success' => false,
                'message' => 'Method tidak didukung',
            ]);
    }
} catch (\Throwable $e) {
    sendJson(500, [
        'success' => false,
        'message' => 'Terjadi kesalahan pada server',
    ]);
}
