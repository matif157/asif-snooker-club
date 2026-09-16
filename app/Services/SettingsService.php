<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Key-value settings service backed by the `settings` table.
 * The Pakistani club phone number & WhatsApp template are configurable here
 * and used across the CRM for click-to-call and messaging.
 */
class SettingsService
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (Database::query('SELECT `key`, `value` FROM settings') as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        }

        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $existing = Database::fetchOne(
            'SELECT id FROM settings WHERE `key` = ?',
            [$key]
        );

        if ($existing) {
            Database::execute(
                'UPDATE settings SET value = ? WHERE `key` = ?',
                [(string) $value, $key]
            );
        } else {
            Database::execute(
                'INSERT INTO settings (`key`, value, `group`) VALUES (?, ?, ?)',
                [$key, (string) $value, $group]
            );
        }

        // Invalidate cache
        self::$cache = null;
    }

    public static function clubName(): string
    {
        return (string) self::get('club_name', config('app.name', 'ASIF SNOOKER CLUB'));
    }

    public static function clubAddress(): string
    {
        return (string) self::get('club_address', 'D Ground, Faisalabad');
    }

    public static function clubPhone(): string
    {
        return (string) self::get('club_phone', env('CLUB_PHONE', '+921234567890'));
    }

    public static function whatsappNumber(): string
    {
        return self::clubPhone();
    }

    /**
     * Monthly expense budget per category (JSON map category => amount).
     */
    public static function expenseBudgets(): array
    {
        $raw = (string) self::get('expense_budgets', '');
        if ($raw === '') {
            return [];
        }
        $map = json_decode($raw, true);
        if (!is_array($map)) {
            return [];
        }
        $clean = [];
        foreach ($map as $cat => $amount) {
            if ((string) $cat === '') {
                continue;
            }
            $clean[(string) $cat] = max(0.0, (float) $amount);
        }
        return $clean;
    }

    public static function whatsappTemplate(): string
    {
        return (string) self::get('whatsapp_template', 'Assalam o Alaikum {name}! Thank you for choosing ' . self::clubName() . '.');
    }

    /**
     * Build a WhatsApp chat link using the configured club number.
     */
    public static function whatsappContactLink(string $message = ''): string
    {
        $digits = preg_replace('/\D+/', '', self::clubPhone()) ?? '';
        $url = 'https://wa.me/' . $digits;
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
    }

    public static function telLink(): string
    {
        $digits = preg_replace('/\D+/', '', self::clubPhone()) ?? '';
        return 'tel:+' . $digits;
    }
}