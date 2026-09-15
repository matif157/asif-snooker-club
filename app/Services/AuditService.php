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
}