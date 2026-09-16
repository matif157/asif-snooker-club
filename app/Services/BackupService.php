<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Portable SQL dump via PDO — works on any shared hosting where
 * mysqldump is unavailable. Produces a restorable .sql file.
 */
class BackupService
{
    public static function backupDir(): string
    {
        $dir = ROOT_PATH . '/storage/backups';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public static function create(): string
    {
        $pdo = Database::connection();

        $filename = 'backup-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.sql';
        $path     = self::backupDir() . '/' . $filename;
        $fp       = fopen($path, 'w');

        fwrite($fp, "-- Asif Snooker Club database backup\n");
        fwrite($fp, "-- Generated: " . date('c') . "\n");
        fwrite($fp, "SET NAMES utf8mb4;\n\n");

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fp, $create[1] . ";\n\n");

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
                $vals = array_map(static function ($v) {
                    if ($v === null) return 'NULL';
                    if (is_int($v) || is_float($v)) return (string) $v;
                    return "'" . str_replace(['\\', "'"], ['\\\\', "''"], (string) $v) . "'";
                }, array_values($row));
                fwrite($fp, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ");\n");
            }
            fwrite($fp, "\n");
        }

        fclose($fp);

        // Roll: keep last 20 backups
        $files = glob(self::backupDir() . '/*.sql');
        if (is_array($files) && count($files) > 20) {
            usort($files, 'strcmp');
            foreach (array_slice($files, 0, count($files) - 20) as $old) {
                unlink($old);
            }
        }

        return $path;
    }

    public static function list(): array
    {
        $files = glob(self::backupDir() . '/backup-*.sql') ?: [];
        $out = [];
        foreach ($files as $f) {
            $out[] = [
                'name' => basename($f),
                'size' => filesize($f),
                'time' => filemtime($f),
                'path' => $f,
            ];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /**
     * Import a backup .sql file back into the database.
     * Takes a snapshot of the current data first in case a rollback is needed.
     */
    public static function restore(string $name, bool $safetyBackup = true): string
    {
        $safe = basename($name);
        $path = self::backupDir() . '/' . $safe;

        if (!preg_match('/^backup-\d{8}-\d{6}(-[0-9a-f]{6})?\.sql$/', $safe) || !file_exists($path)) {
            throw new \RuntimeException('Backup not found or invalid name');
        }

        if ($safetyBackup) {
            self::create(); // pre-restore snapshot
        }

        $pdo = \App\Core\Database::connection();
        $sql = file_get_contents($path);
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
            }
            foreach (self::splitStatements($sql) as $statement) {
                $pdo->exec($statement);
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('Restore failed (a safety backup of the previous state is saved in backups/): ' . $e->getMessage());
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $path;
    }

    /**
     * Accept an uploaded SQL dump (validated) and restore it.
     */
    public static function restoreUploaded(array $file): string
    {
        if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('No file uploaded');
        }
        if (($file['size'] ?? 0) > 20 * 1024 * 1024) {
            throw new \RuntimeException('Backup file must be under 20 MB');
        }
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'sql') {
            throw new \RuntimeException('Only .sql dump files are accepted');
        }

        $target = self::backupDir() . '/backup-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.sql';
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('Could not store the uploaded file');
        }

        return self::restore(basename($target));
    }

    /**
     * Split SQL into individual statements, respecting single-quoted strings
     * (backup dumps can contain quotes, semicolons and backslash escapes).
     */
    public static function splitStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $len        = strlen($sql);
        $inString   = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($inString) {
                $current .= $ch;
                if ($ch === '\\') {
                    if ($i + 1 < $len) {
                        $current .= $sql[++$i];
                    }
                    continue;
                }
                if ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $current .= $sql[++$i];
                        continue;
                    }
                    $inString = false;
                }
                continue;
            }

            if ($ch === "'") {
                $inString = true;
                $current .= $ch;
                continue;
            }

            if ($ch === ';') {
                $trimmed = trim($current);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $current = '';
                continue;
            }

            $current .= $ch;
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}