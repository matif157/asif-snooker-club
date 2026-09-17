<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Payment extends BaseModel
{
    protected string $table = 'payments';

    public const METHODS = [
        'cash'         => 'Cash',
        'jazzcash'     => 'JazzCash',
        'bank_transfer' => 'Online / Bank',
        'card'         => 'Card',
        'other'        => 'Other',
    ];

    /** Accepted method aliases mapped to the canonical enum value. */
    public const METHOD_ALIASES = [
        'online' => 'bank_transfer',
        'bank'   => 'bank_transfer',
        'easypaisa' => 'jazzcash',
    ];

    public const STATUSES = [
        'pending'   => 'Pending',
        'paid'      => 'Paid',
        'failed'    => 'Failed',
        'cancelled' => 'Cancelled',
        'refunded'  => 'Refunded',
        'partial'   => 'Partially Paid',
    ];

    /** Map a user-supplied method (cash / jazzcash / online …) to the enum. */
    public static function normalizeMethod(?string $method): string
    {
        $method = strtolower(trim((string) $method));
        if (isset(self::METHOD_ALIASES[$method])) {
            $method = self::METHOD_ALIASES[$method];
        }

        return array_key_exists($method, self::METHODS) ? $method : 'cash';
    }

    public static function todayRevenueByMethod(): array    {
        return Database::query(
            "SELECT method, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS count
             FROM payments
             WHERE status = 'paid' AND DATE(paid_at) = CURDATE()
             GROUP BY method
             ORDER BY total DESC"
        );
    }

    public static function recent(int $limit = 20): array
    {
        return Database::query(
            "SELECT p.*,
                    c.name AS customer_name,
                    t.number AS table_number,
                    u.name AS acceptor_name
             FROM payments p
             LEFT JOIN customers c ON c.id = p.customer_id
             LEFT JOIN sessions s ON s.id = p.session_id
             LEFT JOIN tables t ON t.id = s.table_id
             LEFT JOIN users u ON u.id = p.accepted_by
             ORDER BY p.id DESC LIMIT {$limit}"
        );
    }

    public static function withDetails(int $id): ?array
    {
        $row = Database::fetchOne(
            "SELECT p.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    t.number AS table_number,
                    t.name AS table_name,
                    s.start_time AS session_start,
                    s.end_time AS session_end,
                    s.amount AS session_amount,
                    s.rate_type AS session_rate,
                    u.name AS acceptor_name
             FROM payments p
             LEFT JOIN customers c ON c.id = p.customer_id
             LEFT JOIN sessions s ON s.id = p.session_id
             LEFT JOIN tables t ON t.id = s.table_id
             LEFT JOIN users u ON u.id = p.accepted_by
             WHERE p.id = ?",
            [$id]
        );

        return $row ?: null;
    }

    public static function outstandingCustomers(int $limit = 10): array
    {
        return Database::query(
            "SELECT id, name, phone, whatsapp, outstanding_balance, last_visit_at
             FROM customers
             WHERE outstanding_balance > 0 AND status = 'active'
             ORDER BY outstanding_balance DESC
             LIMIT {$limit}"
        );
    }
}