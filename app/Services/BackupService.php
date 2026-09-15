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

        $filename = 'backup-' . date('Ymd-His') . '.sql';
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
}