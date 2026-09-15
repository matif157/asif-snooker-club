<?php

declare(strict_types=1);

return [
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => (int) env('DB_PORT', 3306),
    'database' => env('DB_NAME', 'asif_snooker_club'),
    'username' => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset'  => 'utf8mb4',
];