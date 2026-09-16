<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

/**
 * Fresh-install engine shared by the web installer (/install)
 * and the CLI installer (database/install.php).
 */
class InstallerService
{
    public const ENV_TEMPLATE = <<<'ENV'
# APP
APP_NAME="ASIF SNOOKER CLUB"
APP_ENV=production
APP_DEBUG=false
APP_URL={APP_URL}
APP_TIMEZONE=Asia/Karachi
APP_CURRENCY=Rs

# DATABASE
DB_HOST={DB_HOST}
DB_PORT={DB_PORT}
DB_NAME={DB_NAME}
DB_USER={DB_USER}
DB_PASS={DB_PASS}

# CLUB SETTINGS
CLUB_NAME="{CLUB_NAME}"
CLUB_PHONE="{CLUB_PHONE}"
CLUB_ADDRESS="{CLUB_ADDRESS}"

# ADMIN ACCOUNT (used by database/install.php)
ADMIN_EMAIL={ADMIN_EMAIL}
ENV;

    public static function isInstalled(): bool
    {
        $db = config('database');
        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']),
                $db['username'],
                $db['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $rows = $pdo->query('SELECT COUNT(*) FROM settings')->fetchColumn();
            return (int) $rows > 0;
        } catch (PDOException) {
            return false;
        }
    }

    public static function requirements(): array
    {
        $storage = ROOT_PATH . '/storage';
        return [
            ['name' => 'PHP 8.2+',           'ok' => version_compare(PHP_VERSION, '8.2.0', '>='), 'detail' => PHP_VERSION],
            ['name' => 'PDO MySQL extension', 'ok' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? 'loaded' : 'missing'],
            ['name' => 'JSON extension',      'ok' => extension_loaded('json'), 'detail' => extension_loaded('json') ? 'loaded' : 'missing'],
            ['name' => 'Storage writable',    'ok' => is_writable($storage), 'detail' => $storage],
        ];
    }

    /**
     * DSN with database name optional (lets us CREATE DATABASE first).
     */
    private static function pdo(array $cfg, bool $withDb = false): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $cfg['host'], $cfg['port']);
        if ($withDb) {
            $dsn .= ';dbname=' . $cfg['db'];
        }
        return new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public static function testConnection(array $cfg): array
    {
        try {
            self::pdo($cfg);
            return ['ok' => true, 'message' => 'Connected to MySQL successfully'];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public static function runMigrations(PDO $pdo): int
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            file VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $count = 0;
        $files = glob(ROOT_PATH . '/database/migrations/*.sql');
        sort($files);

        foreach ($files as $file) {
            $basename = basename($file);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE file = ?');
            $stmt->execute([$basename]);
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }
            $pdo->exec(file_get_contents($file));
            $insert = $pdo->prepare('INSERT INTO migrations (file) VALUES (?)');
            $insert->execute([$basename]);
            $count++;
        }

        return $count;
    }

    public static function runSeeders(PDO $pdo): int
    {
        $count = 0;
        $files = glob(ROOT_PATH . '/database/seeders/*.sql');
        sort($files);

        foreach ($files as $file) {
            $basename = 'seed_' . basename($file);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE file = ?');
            $stmt->execute([$basename]);
            if ((int) $stmt->fetchColumn() > 0) {
                continue;
            }
            $pdo->exec(file_get_contents($file));
            $insert = $pdo->prepare('INSERT INTO migrations (file) VALUES (?)');
            $insert->execute([$basename]);
            $count++;
        }

        return $count;
    }

    public static function createOwner(PDO $pdo, string $name, string $email, string $password): void
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role IN ("owner","admin")');
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, NULL, ?, ?)');
        $insert->execute([$name, $email, password_hash($password, PASSWORD_ARGON2ID), 'owner']);
    }

    /**
     * Run the full install: create DB, migrate, seed, owner account, write .env.
     */
    public static function install(array $cfg): array
    {
        $pdo = self::pdo($cfg); // no db yet
        $dbName = preg_replace('/[^A-Za-z0-9_]/', '', (string) $cfg['db']);
        if ($dbName === '') {
            throw new \RuntimeException('Invalid database name');
        }

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        $migrations = self::runMigrations($pdo);
        $seeders    = self::runSeeders($pdo);
        self::createOwner($pdo, $cfg['admin_name'], $cfg['admin_email'], $cfg['admin_password']);

        self::writeEnvFile($cfg);

        return [
            'database'    => $dbName,
            'migrations'  => $migrations,
            'seeders'     => $seeders,
            'owner'       => $cfg['admin_email'],
        ];
    }

    /**
     * Write .env from provided values (no template file needed on shared hosts).
     */
    public static function writeEnvFile(array $cfg): string
    {
        $replace = [
            '{DB_HOST}'    => $cfg['host'] ?? '127.0.0.1',
            '{DB_PORT}'    => (int) ($cfg['port'] ?? 3306),
            '{DB_NAME}'    => $cfg['db'],
            '{DB_USER}'    => $cfg['user'],
            '{DB_PASS}'    => (string) ($cfg['pass'] ?? ''),
            '{CLUB_NAME}'  => $cfg['club_name'] ?? 'Asif Snooker Club',
            '{CLUB_PHONE}' => $cfg['club_phone'] ?? '',
            '{CLUB_ADDRESS}'=> $cfg['club_address'] ?? '',
            '{ADMIN_EMAIL}'=> $cfg['admin_email'],
            '{APP_URL}'    => $cfg['app_url'] ?? self::guessAppUrl(),
        ];

        $env = str_replace(array_keys($replace), array_values($replace), self::ENV_TEMPLATE);
        $path = ROOT_PATH . '/.env';
        file_put_contents($path, $env);

        chmod($path, 0644);
        return $path;
    }

    public static function guessAppUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}