<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Table extends BaseModel
{
    protected string $table = 'tables';

    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_OCCUPIED    = 'occupied';
    public const STATUS_RESERVED    = 'reserved';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_BLOCKED     = 'blocked';
    public const STATUS_OFFLINE     = 'offline';

    public const STATUS_LABELS = [
        'available'   => 'Available',
        'occupied'    => 'Occupied',
        'reserved'    => 'Reserved',
        'maintenance' => 'Maintenance',
        'blocked'     => 'Blocked',
        'offline'     => 'Offline',
    ];

    public static function activeTables(): array
    {
        return Database::query(
            "SELECT * FROM tables WHERE is_active = 1 ORDER BY sort_order ASC, number ASC"
        );
    }

    public function currentSession(): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM sessions
             WHERE table_id = ? AND status IN ("active","paused")
             ORDER BY id DESC LIMIT 1',
            [$this->id]
        );
    }

    public function upcomingBooking(): ?array
    {
        return Database::fetchOne(
            'SELECT * FROM bookings
             WHERE table_id = ? AND booking_date = CURDATE()
               AND status IN ("requested","confirmed","arrived")
             ORDER BY start_time ASC LIMIT 1',
            [$this->id]
        );
    }

    public function syncStatusFromSession(): void
    {
        $session = Database::fetchOne(
            'SELECT id FROM sessions
             WHERE table_id = ? AND status IN ("active","paused")
             LIMIT 1',
            [$this->id]
        );

        if ($session !== null) {
            Database::execute('UPDATE tables SET status = "occupied" WHERE id = ?', [$this->id]);
        } else {
            Database::execute(
                'UPDATE tables SET status = "available"
                 WHERE id = ? AND status = "occupied"',
                [$this->id]
            );
        }
    }
}