<?php

session_start();

require __DIR__ . '/../config/app.php';

use App\Core\Router;

$router = new Router();
require __DIR__ . '/../routes/web.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Buang BASE_URL dari depan URI supaya sisanya jadi path route, misal "/mahasiswa"
if (strpos($uri, BASE_URL) === 0) {
    $uri = substr($uri, strlen(BASE_URL));
}

$router->dispatch($uri, $_SERVER['REQUEST_METHOD']);
