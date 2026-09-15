<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    private static array $config = [];

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            self::$config = require ROOT_PATH . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['database'],
                self::$config['charset']
            );

            self::$instance = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                [
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_STRINGIFY_FETCHES   => false,
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                ]
            );
        }

        return self::$instance;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) self::connection()->lastInsertId();
    }
}