<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Booking extends BaseModel
{
    protected string $table = 'bookings';

    public const STATUS_REQUESTED  = 'requested';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_ARRIVED    = 'arrived';
    public const STATUS_ACTIVE     = 'active';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_NO_SHOW    = 'no_show';
    public const STATUS_EXPIRED    = 'expired';
    public const STATUS_PAID       = 'paid';

    public const STATUS_LABELS = [
        'requested'  => 'Requested',
        'confirmed'  => 'Confirmed',
        'arrived'    => 'Arrived',
        'active'     => 'Active',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
        'no_show'    => 'No Show',
        'expired'    => 'Expired',
        'paid'       => 'Paid',
    ];

    public const STATUS_COLORS = [
        'requested'  => 'amber',
        'confirmed'  => 'sky',
        'arrived'    => 'violet',
        'active'     => 'green',
        'completed'  => 'slate',
        'cancelled'  => 'rose',
        'no_show'    => 'rose',
        'expired'    => 'slate',
        'paid'       => 'emerald',
    ];

    public static function forDate(string $date): array
    {
        return Database::query(
            "SELECT b.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    c.name AS customer_linked_name,
                    c.phone AS customer_linked_phone
             FROM bookings b
             JOIN tables t ON t.id = b.table_id
             LEFT JOIN customers c ON c.id = b.customer_id
             WHERE b.booking_date = ?
             ORDER BY b.start_time ASC",
            [$date]
        );
    }

    public static function upcoming(int $limit = 10): array
    {
        return Database::query(
            "SELECT b.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    c.name AS customer_linked_name
             FROM bookings b
             JOIN tables t ON t.id = b.table_id
             LEFT JOIN customers c ON c.id = b.customer_id
             WHERE b.booking_date >= CURDATE()
               AND b.status IN ('requested','confirmed','arrived')
             ORDER BY b.booking_date ASC, b.start_time ASC
             LIMIT {$limit}"
        );
    }

    public static function isTableFree(int $tableId, string $date, string $start, string $end, ?int $ignoreId = null): bool
    {
        $sql = "SELECT COUNT(*) AS c
                FROM bookings
                WHERE table_id = ?
                  AND booking_date = ?
                  AND status IN ('requested','confirmed','arrived','active')
                  AND start_time < ? AND end_time > ?";

        $params = [
            $tableId,
            $date,
            $end,
            $start,
        ];

        if ($ignoreId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }

        $row = Database::fetchOne($sql, $params);
        return (int) ($row['c'] ?? 0) === 0;
    }

    /**
     * Expire old no-show / unconfirmed bookings older than now.
     */
    public static function expirePast(): int
    {
        $affected = 0;
        $rows = Database::query(
            "SELECT id FROM bookings
             WHERE status IN ('requested','confirmed')
               AND (booking_date < CURDATE()
                 OR (booking_date = CURDATE() AND end_time <= CURTIME()))"
        );

        foreach ($rows as $row) {
            Database::execute(
                "UPDATE bookings SET status = 'expired' WHERE id = ?",
                [$row['id']]
            );
            $affected++;
        }

        return $affected;
    }

    /**
     * Mark unconfirmed bookings whose slot has fully passed as no-show.
     */
    public static function markNoShows(): int
    {
        $affected = 0;
        $rows = Database::query(
            "SELECT id FROM bookings
             WHERE status IN ('arrived')
               AND end_time < CURTIME()
               AND booking_date <= CURDATE()"
        );

        foreach ($rows as $row) {
            Database::execute(
                "UPDATE bookings SET status = 'completed' WHERE id = ?",
                [$row['id']]
            );
            $affected++;
        }

        return $affected;
    }

    /**
     * When a session starts on a table, mark any of today's confirmed/arrived
     * bookings for that table as 'active' and return the booking id if found.
     */
    public static function activateForTable(int $tableId): ?int
    {
        $row = Database::fetchOne(
            "SELECT id FROM bookings
             WHERE table_id = ?
               AND booking_date = CURDATE()
               AND status IN ('requested','confirmed','arrived')
             ORDER BY start_time ASC LIMIT 1",
            [$tableId]
        );

        if ($row) {
            Database::execute(
                "UPDATE bookings SET status = 'active' WHERE id = ?",
                [$row['id']]
            );
            return (int) $row['id'];
        }

        return null;
    }
}