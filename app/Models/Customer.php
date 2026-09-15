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