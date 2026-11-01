<?php
spl_autoload_register(function ($class) {
    $folders = ['config', 'core', 'models', 'controllers'];
    foreach ($folders as $folder) {
        $path = __DIR__ . "/../{$folder}/{$class}.php";
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});
