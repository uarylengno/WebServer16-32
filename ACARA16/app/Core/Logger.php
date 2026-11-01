<?php

namespace App\Core;

class Logger
{
    private static function path(): string
    {
        return __DIR__ . '/../../storage/logs/app.log';
    }

    public static function error(string $message): void
    {
        $line = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        error_log($line, 3, self::path());
    }

    public static function info(string $message): void
    {
        $line = date('Y-m-d H:i:s') . ' - INFO - ' . $message . PHP_EOL;
        error_log($line, 3, self::path());
    }
}
