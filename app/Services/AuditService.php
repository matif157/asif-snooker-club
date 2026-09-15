<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;

/**
 * Lightweight audit logging of meaningful business actions.
 */
class AuditService
{
    public static function log(
        string $action,
        ?string $entity = null,
        int|string|null $recordId = null,
        mixed $oldValue = null,
        mixed $newValue = null
    ): void {
        $userId = Auth::id();

        Database::execute(
            "INSERT INTO audit_log (user_id, action, entity, record_id, old_value, new_value, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $action,
                $entity,
                $recordId !== null ? (string) $recordId : null,
                is_array($oldValue) ? json_encode($oldValue) : (is_scalar($oldValue) ? (string) $oldValue : null),
                is_array($newValue) ? json_encode($newValue) : (is_scalar($newValue) ? (string) $newValue : null),
                Request::ip(),
            ]
        );
    }

    public static function recent(int $limit = 30): array
    {
        return Database::query(
            "SELECT a.*, u.name AS user_name
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT {$limit}"
        );
    }

    /**
     * Filtered, paginated audit trail for the dedicated audit page.
     */
    public static function search(
        ?string $action = null,
        ?string $entity = null,
        ?string $from = null,
        ?string $to = null,
        int $offset = 0,
        int $perPage = 50
    ): array {
        $where = [];
        $params = [];

        if ($action) { $where[] = 'a.action = ?'; $params[] = $action; }
        if ($entity) { $where[] = 'a.entity = ?'; $params[] = $entity; }
        if ($from)  { $where[] = 'DATE(a.created_at) >= ?'; $params[] = $from; }
        if ($to)    { $where[] = 'DATE(a.created_at) <= ?'; $params[] = $to; }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = Database::fetchOne(
            "SELECT COUNT(*) AS c FROM audit_log a {$whereSql}",
            $params
        );

        $params[] = $perPage;
        $params[] = $offset;

        $rows = Database::query(
            "SELECT a.*, u.name AS user_name
             FROM audit_log a
             LEFT JOIN users u ON u.id = a.user_id
             {$whereSql}
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return [
            'total'   => (int) ($total['c'] ?? 0),
            'rows'    => $rows,
            'perPage' => $perPage,
            'offset'  => $offset,
        ];
    }

    private static function actions(): array
    {
        return Database::query(
            'SELECT DISTINCT action FROM audit_log ORDER BY action ASC'
        );
    }

    public static function actionList(): array
    {
        return array_map(fn($r) => $r['action'], self::actions());
    }

    public static function entityList(): array
    {
        $rows = Database::query(
            'SELECT DISTINCT entity FROM audit_log WHERE entity IS NOT NULL ORDER BY entity ASC'
        );
        return array_map(fn($r) => $r['entity'], $rows);
    }
}