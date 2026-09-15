<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Expense extends BaseModel
{
    protected string $table = 'expenses';

    public const CATEGORIES = [
        'electricity' => 'Electricity',
        'labour'      => 'Labour',
        'rent'        => 'Rent',
        'maintenance' => 'Maintenance',
        'cleaning'    => 'Cleaning',
        'supplies'    => 'Supplies',
        'internet'    => 'Internet',
        'security'    => 'Security',
        'camera'      => 'Camera/CCTV',
        'other'       => 'Other',
    ];

    public const STATUSES = [
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public static function forRange(string $from, string $to): array
    {
        return Database::query(
            'SELECT * FROM expenses
             WHERE expense_date BETWEEN ? AND ?
             ORDER BY expense_date DESC',
            [$from, $to]
        );
    }

    public static function todayTotal(): float
    {
        $row = Database::fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM expenses WHERE expense_date = CURDATE()"
        );
        return (float) ($row['total'] ?? 0);
    }
}