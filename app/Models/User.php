<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User extends BaseModel
{
    protected string $table = 'users';

    public static function findByEmail(string $email): ?static
    {
        return static::findBy('email', $email);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        Database::execute(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($plainPassword, PASSWORD_ARGON2ID), $id]
        );
    }

    public static function updateLastLogin(int $id): void
    {
        Database::execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?',
            [$id]
        );
    }

    public static function hasPermission(int $userId, string $permission): bool
    {
        // Owner role bypasses all checks
        $user = static::find($userId);
        if ($user?->role === 'owner' || $user?->role === 'admin') {
            return true;
        }

        if ($user === null) {
            return false;
        }

        $row = Database::fetchOne(
            "SELECT COUNT(*) AS c
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role = ? AND p.name = ?",
            [$user->role, $permission]
        );

        return (int) ($row['c'] ?? 0) > 0;
    }

    public static function isOwner(int $userId): bool
    {
        $user = static::find($userId);
        return $user?->role === 'owner';
    }
}