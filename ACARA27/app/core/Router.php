<?php
class Router {
    // Peta sederhana: nama resource pada URL => nama class Controller
    private array $map = [
        'mahasiswa' => 'MahasiswaController',
        'buku'      => 'BukuController',
        'register'  => 'AuthController',   // Acara 19
        'login'     => 'AuthController',   // Acara 20
    ];

    public function dispatch(): void {
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $segments = $uri === '' ? [] : explode('/', $uri);

        // Cari nama resource dari ujung URL, sehingga tetap jalan di folder
        // mana pun proyek ditaruh (mis. /sistem-informasi-akademik/public/mahasiswa).
        $resource = null;
        $controllerName = null;
        $last = end($segments);
        if ($last !== false && isset($this->map[$last])) {
            $resource = $last;
            $controllerName = $this->map[$last];
        }

        if (!$controllerName) {
            Response::json(404, false, 'Endpoint tidak ditemukan');
        }

        $controller = new $controllerName();

        // 'register' dan 'login' bukan resource CRUD biasa: masing-masing hanya satu aksi lewat POST.
        if ($resource === 'register' || $resource === 'login') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Allow: POST');
                Response::json(405, false, 'Method tidak diizinkan, gunakan POST');
            }
            $controller->$resource();
            return;
        }

        // Acara 26: PHP hanya mengisi $_POST dan $_FILES untuk request POST multipart, TIDAK untuk PUT.
        // Karena itu mengubah data sekaligus mengganti foto dikirim sebagai POST ...?id=1&_method=PUT.
        // Hanya nilai PUT yang dikenali (daftar putih); nilai lain diabaikan dan request tetap diperlakukan sebagai POST.
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST' && ($_GET['_method'] ?? '') === 'PUT') {
            $method = 'PUT';
        }

        switch ($method) {
            case 'GET':    $controller->index(); break;
            case 'POST':   $controller->store(); break;
            case 'PUT':    $controller->update($_GET['id'] ?? null); break;
            case 'DELETE': $controller->destroy($_GET['id'] ?? null); break;
            default:
                header('Allow: GET, POST, PUT, DELETE');
                Response::json(405, false, 'Method tidak diizinkan');
        }
    }
}
