<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ClubSession extends BaseModel
{
    protected string $table = 'sessions';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_PAUSED    = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_UNPAID  = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID    = 'paid';
    public const PAYMENT_REFUNDED = 'refunded';

    public const RATE_TYPES = [
        'hourly'   => 'Hourly',
        'frame'    => 'Per Frame',
        'peak'     => 'Peak Rate',
        'off_peak' => 'Off-Peak Rate',
        'vip'      => 'VIP Rate',
        'night'    => 'Night Rate',
        'custom'   => 'Custom',
    ];

    public function table(): ?array
    {
        return Table::find((int) $this->table_id)?->toArray();
    }

    public function customer(): ?array
    {
        return $this->customer_id ? Customer::find((int) $this->customer_id)?->toArray() : null;
    }

    /**
     * Calculate effective billed time in seconds.
     * Respects pause durations.
     */
    public function billedSeconds(?\DateTimeInterface $now = null): int
    {
        $now = $now ?? new \DateTimeImmutable();

        if ($this->status === self::STATUS_ACTIVE) {
            $end = $now;
            if ($this->paused_at) {
                $end = new \DateTimeImmutable($this->paused_at);
            }
        } else {
            $end = new \DateTimeImmutable($this->end_time ?? $this->start_time);
        }

        $total = $end->getTimestamp() - (new \DateTimeImmutable($this->start_time))->getTimestamp();
        $total -= (int) $this->paused_total_sec;

        return max(0, $total);
    }

    /**
     * Compute current charge given rate type & elapsed seconds.
     */
    public function computeAmount(?\DateTimeInterface $now = null): float
    {
        $now = $now ?? new \DateTimeImmutable();
        $seconds = $this->billedSeconds($now);

        $rate = (float) $this->rate;
        if ($rate <= 0) {
            $rate = (float) ($this->table()['hourly_rate'] ?? 300);
        }

        $hours = $seconds / 3600.0;
        $amount = round($hours * $rate, 0);

        // Apply minimum charge
        $minCharge = (float) ($this->table()['min_charge'] ?? 100);
        if ($amount < $minCharge && $seconds > 0) {
            $amount = $minCharge;
        }

        // Round up to nearest 10 (common in Pakistani clubs)
        if ($amount > 0) {
            $amount = ceil($amount / 10) * 10;
        }

        $amount += (float) $this->extra_charges;
        $amount -= (float) $this->discount;

        return max(0, $amount);
    }

    public static function activeSessions(): array
    {
        return Database::query(
            "SELECT s.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    t.hourly_rate AS table_rate,
                    t.min_charge AS table_min_charge,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.whatsapp AS customer_whatsapp,
                    u.name AS staff_name
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.staff_id
             WHERE s.status IN ('active','paused')
             ORDER BY s.start_time ASC"
        );
    }

    public static function recent(int $limit = 30): array
    {
        return Database::query(
            "SELECT s.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    c.name AS customer_name,
                    u.name AS staff_name
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.staff_id
             ORDER BY s.id DESC LIMIT {$limit}"
        );
    }

    /**
     * Find the current active session for a table (or null).
     */
    public static function activeForTable(int $tableId): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM sessions
             WHERE table_id = ? AND status IN ('active','paused')
             ORDER BY id DESC LIMIT 1",
            [$tableId]
        );
    }

    public static function withDetails(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT s.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    u.name AS staff_name,
                    (SELECT COALESCE(SUM(p.amount), 0) FROM payments p
                     WHERE p.session_id = s.id AND p.status = 'paid') AS paid_total
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.staff_id
             WHERE s.id = ?",
            [$id]
        );
    }

    public static function todayStats(): array
    {
        return Database::fetchOne(
            "SELECT COUNT(*) AS count,
                    COALESCE(SUM(amount), 0) AS revenue,
                    COALESCE(SUM(
                        CASE WHEN payment_status IN ('partial','paid') THEN amount ELSE 0 END
                    ), 0) AS collected
             FROM sessions
             WHERE DATE(created_at) = CURDATE() AND status NOT IN ('cancelled')"
        ) ?? ['count' => 0, 'revenue' => 0, 'collected' => 0];
    }
}