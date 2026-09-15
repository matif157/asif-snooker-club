<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    private const SESSION_KEY = 'auth_user_id';

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Rehash if needed (PHP auto-rehashes with Argon2id)
        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            User::updatePassword($user['id'], $password);
        }

        Session::set(self::SESSION_KEY, (int) $user['id']);
        return true;
    }

    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public static function user(): ?User
    {
        $id = Session::get(self::SESSION_KEY);
        if ($id === null) {
            return null;
        }

        return User::find((int) $id);
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user?->id;
    }

    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }

        return User::hasPermission($user->id, $permission);
    }
}