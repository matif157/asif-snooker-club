<?php

declare(strict_types=1);

/**
 * CLI database backup.
 * Usage:
 *   php database/backup.php [output.sql]
 *
 * Produces a portable SQL dump of the whole DB (works on shared hosting).
 * Tip for cron (nightly at 02:30):
 *   30 2 * * * /usr/bin/php /path/to/asif-snooker-club/database/backup.php
 */

define('ROOT_PATH', dirname(__DIR__));

// Load helpers (env, config expose DB creds)
require ROOT_PATH . '/vendor/autoload.php';

load_env(ROOT_PATH . '/.env');
// just a backup file 

$db = require ROOT_PATH . '/config/database.php';
if (!$db['database'] || $db['database'] === 'asif_snooker_club') {
    echo "Using database: {$db['database']}@{$db['host']}:{$db['port']}\n";
}

$service = new \App\Services\BackupService();
$path = $service->create();

$target = $argv[1] ?? null;
if ($target !== null) {
    $abs = strpos($target, '/') === 0 ? $target : ROOT_PATH . '/' . $target;
    $dir = dirname($abs);
    if (is_dir($dir)) {
        rename($path, $abs);
        $path = $abs;
    }
}

echo "Backup saved: {$path}\n";
echo 'Size: ' . number_format(round(filesize($path) / 1024)) . " KB\n";