<?php

declare(strict_types=1);

/**
 * Database installer / seeder.
 * Usage:
 *   php database/install.php
 * Requires .env to be configured.
 */

define('ROOT_PATH', dirname(__DIR__));

// Minimal .env loader (self-contained for CLI install)
function loadEnv(): void
{
    $file = ROOT_PATH . '/.env';
    if (!file_exists($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value, "\"'");
        if (getenv($key) === false && !defined($key)) {
            putenv("{$key}={$value}");
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    return getenv($key) !== false ? getenv($key) : $default;
}

loadEnv();

try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'),
            (int) env('DB_PORT', 3306),
        ),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "✗ Database connection failed: {$e->getMessage()}\n";
    echo "  Please check config/.env values.\n";
    exit(1);
}

$dbName = env('DB_NAME', 'asif_snooker_club');

// Create database if not exists
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$dbName}`");

echo "✓ Connected to MySQL\n";
echo "✓ Database '{$dbName}' ready\n";

// Run migrations
$migrationFiles = glob(ROOT_PATH . '/database/migrations/*.sql');
sort($migrationFiles);

// Track applied migrations
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    file VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach ($migrationFiles as $file) {
    $basename = basename($file);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE file = ?');
    $stmt->execute([$basename]);

    if ((int) $stmt->fetchColumn() > 0) {
        echo "  ↦ {$basename} already applied, skipping\n";
        continue;
    }

    echo "  → Applying {$basename}... ";

    $sql = file_get_contents($file);
    // Split by multi-statement (comment lines are safe inside pdo->exec for MySQL)
    $pdo->exec($sql);

    $stmt = $pdo->prepare('INSERT INTO migrations (file) VALUES (?)');
    $stmt->execute([$basename]);
    echo "done\n";
}

// Run seeders
$seederFiles = glob(ROOT_PATH . '/database/seeders/*.sql');
sort($seederFiles);

foreach ($seederFiles as $file) {
    $basename = basename($file);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE file = ?');
    $stmt->execute(['seed_' . $basename]);

    if ((int) $stmt->fetchColumn() > 0) {
        echo "  ↦ {$basename} already applied, skipping\n";
        continue;
    }

    echo "  → Seeding {$basename}... ";
    $pdo->exec(file_get_contents($file));
    $stmt = $pdo->prepare('INSERT INTO migrations (file) VALUES (?)');
    $stmt->execute(['seed_' . $basename]);
    echo "done\n";
}

// Check if an admin user exists
$adminEmail = env('ADMIN_EMAIL', 'admin@asifclub.pk');
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role IN ("owner","admin")');
$stmt->execute();
$adminCount = (int) $stmt->fetchColumn();

if ($adminCount === 0) {
    echo "\n── Create Owner / Admin Account ────────────────────────────\n";
    $name = readline("Full name [Club Owner]: ") ?: 'Club Owner';
    $email = readline("Email [{$adminEmail}]: ") ?: $adminEmail;
    $password = '';
    $confirm = '';
    while ($password === '' || $password !== $confirm) {
        system('stty -echo 2>/dev/null');
        $password = readline("Password: ");
        system('stty echo 2>/dev/null');
        echo "\n";
        if ($password === '') {
            continue;
        }
        system('stty -echo 2>/dev/null');
        $confirm = readline("Confirm password: ");
        system('stty echo 2>/dev/null');
        echo "\n";
        if ($password !== $confirm) {
            echo "  ✗ Passwords do not match. Try again.\n";
        }
    }

    $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, env('CLUB_PHONE', NULL), password_hash($password, PASSWORD_ARGON2ID), 'owner']);
    echo "✓ Owner account created for {$email}\n";
} else {
    echo "✓ Owner/admin account already exists\n";
}

echo "\n✓ Installation complete!\n";
echo "  Start server:  cd public && php -S localhost:8000\n\n";