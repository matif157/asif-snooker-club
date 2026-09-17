<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Customer extends BaseModel
{
    protected string $table = 'customers';

    public const CATEGORIES = [
        'regular'    => 'Regular',
        'vip'        => 'VIP',
        'member'     => 'Member',
        'tournament' => 'Tournament Player',
        'inactive'   => 'Inactive',
    ];

    public static function search(string $term): array
    {
        if ($term === '') {
            return Database::query(
                "SELECT * FROM customers WHERE status = 'active'
                 ORDER BY last_visit_at DESC, name ASC LIMIT 10"
            );
        }

        return Database::query(
            "SELECT * FROM customers
             WHERE name LIKE :term
                OR phone LIKE :term
                OR whatsapp LIKE :term
                OR email LIKE :term
             ORDER BY name ASC LIMIT 10",
            ['term' => "%{$term}%"]
        );
    }

    /**
     * Look up a customer by phone (either column), tolerant of formatting.
     */
    public static function findByPhone(string $phone): ?array
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        // Normalise to an international 92XXXXXXXXX form for matching
        $intl = $digits;
        if (strlen($intl) === 11 && str_starts_with($intl, '0')) {
            $intl = '92' . substr($intl, 1);          // 03001234567 -> 923001234567
        } elseif (strlen($intl) === 10) {
            $intl = '92' . $intl;                     // 3001234567   -> 923001234567
        } elseif (str_starts_with($intl, '92') && strlen($intl) === 12) {
            $intl = $digits;
        }
        $intl = preg_replace('/\D+/', '', $intl) ?? '';

        $row = Database::fetchOne(
            "SELECT * FROM customers
             WHERE REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', '') = ?
                OR REPLACE(REPLACE(REPLACE(whatsapp, '+', ''), ' ', ''), '-', '') = ?
             LIMIT 1",
            [$intl, $intl]
        );

        return $row ?: null;
    }

    /**
     * Store (or reset) a member's 4-digit portal PIN. Always re-hashed.
     */
    public static function setPortalPin(int $id, string $pin): bool
    {
        $pin = preg_replace('/\D+/', '', $pin) ?? '';
        if (strlen($pin) !== 4) {
            return false;
        }

        Database::query(
            'UPDATE customers SET portal_pin = ? WHERE id = ?',
            [password_hash($pin, PASSWORD_DEFAULT), $id]
        );

        return true;
    }

    public static function verifyPortalPin(int $id, string $pin): bool
    {
        $row = Database::fetchOne('SELECT portal_pin FROM customers WHERE id = ?', [$id]);
        if (!$row || empty($row['portal_pin'])) {
            return false;
        }

        return password_verify($pin, $row['portal_pin']);
    }

    /**
     * Customers with a reachable phone number for WhatsApp broadcast.
     */
    public static function audience(string $audience = 'active', int $limit = 200): array
    {
        $where = "AND (phone IS NOT NULL AND phone != '')";
        $params = [];

        switch ($audience) {
            case 'outstanding':
                $where .= ' AND outstanding_balance > 0';
                $order = 'outstanding_balance DESC';
                break;
            case 'recent':
                $params[] = date('Y-m-d', strtotime('-30 days'));
                $where .= ' AND last_visit_at >= ?';
                $order = 'last_visit_at DESC';
                break;
            case 'member':
                $where .= " AND category IN ('member','vip','regular')";
                $order = 'last_visit_at DESC';
                break;
            default:
                $order = 'last_visit_at DESC';
                break;
        }

        $limit = min(500, max(1, $limit));
        $params[] = $limit;

        return Database::query(
            "SELECT id, name, phone, outstanding_balance, total_spent, total_visits, last_visit_at
             FROM customers
             WHERE status = 'active' {$where}
             ORDER BY {$order}
             LIMIT ?",
            $params
        );
    }

    public function sessions(): array
    {
        return Database::query(
            'SELECT * FROM sessions WHERE customer_id = ? ORDER BY id DESC LIMIT 50',
            [$this->id]
        );
    }

    public function payments(): array
    {
        return Database::query(
            'SELECT * FROM payments WHERE customer_id = ? ORDER BY id DESC LIMIT 50',
            [$this->id]
        );
    }

    public function bookings(): array
    {
        return Database::query(
            'SELECT * FROM bookings WHERE customer_id = ? ORDER BY booking_date DESC, start_time DESC LIMIT 50',
            [$this->id]
        );
    }

    public static function incrementStats(int $customerId, float $hours, float $spent, float $outstanding): void
    {
        Database::execute(
            'UPDATE customers
             SET total_visits = total_visits + 1,
                 total_hours = total_hours + ?,
                 total_spent = total_spent + ?,
                 outstanding_balance = outstanding_balance + ?,
                 last_visit_at = NOW(),
                 status = "active"
             WHERE id = ?',
            [$hours, $spent, $outstanding, $customerId]
        );
    }

    /**
     * Increase (positive) or reduce (negative) a customer's loan balance.
     * Never lets the ledger go below zero, and returns the new balance.
     */
    public static function adjustOutstanding(int $customerId, float $delta): float
    {
        Database::execute(
            'UPDATE customers
             SET outstanding_balance = GREATEST(0, outstanding_balance + ?)
             WHERE id = ?',
            [$delta, $customerId]
        );

        $row = Database::fetchOne('SELECT outstanding_balance FROM customers WHERE id = ?', [$customerId]);
        return (float) ($row['outstanding_balance'] ?? 0);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }

        // Convert 03XXXXXXXXX (Pakistan local) to +92XXXXXXXXX
        if (strlen($digits) === 11 && str_starts_with($digits, '03')) {
            return '+92' . substr($digits, 1);
        }

        // Convert 0XXXXXXXXX (10-digit landline) -> +92XXXXXXXXX
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+92' . substr($digits, 1);
        }

        // Already international
        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            return '+' . $digits;
        }

        return $phone;
    }

    public static function telLink(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return 'tel:+' . $digits;
    }

    public static function whatsappLink(string $phone, string $message = ''): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $url = 'https://wa.me/' . $digits;

        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }
}