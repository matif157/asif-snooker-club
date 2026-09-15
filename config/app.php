<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name'      => env('APP_NAME', 'ASIF SNOOKER CLUB'),
    'env'       => env('APP_ENV', 'production'),
    'debug'     => env('APP_DEBUG', false) === 'true' || env('APP_DEBUG', false) === '1',
    'url'       => env('APP_URL', 'http://localhost'),
    'timezone'  => env('APP_TIMEZONE', 'Asia/Karachi'),
    'currency'  => env('APP_CURRENCY', 'Rs'),
];