<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Customer;

/**
 * Scheduled WhatsApp reminder engine.
 *
 * Zero-cost by design: no third-party API. Candidates are prepared with
 * wa.me deep links and written to storage/reminders for quick sending via
 * WhatsApp Web, while dedup columns (customers.last_reminded_at,
 * bookings.reminder_sent_at) stop repeat reminders.
 */
class ReminderService
{
    public static function setting(string $key, string $default = ''): string
    {
        $all = SettingsService::all();
        return (string) ($all[$key] ?? $default);
    }

    public static function enabled(): bool
    {
        return self::setting('reminder_enabled', '0') === '1';
    }

    public static function horizonMinutes(): int
    {
        return max(15, (int) self::setting('reminder_horizon_min', '120'));
    }

    public static function clubName(): string
    {
        return self::setting('club_name', 'Asif Snooker Club');
    }

    public static function currency(): string
    {
        return self::setting('currency', 'Rs');
    }

    /**
     * Confirmed/requested bookings starting within the reminder horizon.
     */
    public static function bookingCandidates(): array
    {
        $horizon = self::horizonMinutes();

        $rows = Database::query(
            "SELECT b.id, b.booking_date, b.start_time, b.players_count,
                    b.customer_name, b.customer_phone,
                    c.name AS cust_linked_name, c.phone AS cust_linked_phone,
                    t.number AS table_number
             FROM bookings b
             LEFT JOIN tables t      ON t.id  = b.table_id
             LEFT JOIN customers c   ON c.id  = b.customer_id
             WHERE b.status IN ('requested','confirmed')
               AND b.booking_date = CURDATE()
               AND b.reminder_sent_at IS NULL
               AND b.start_time > CURTIME()
               AND TIMESTAMPDIFF(MINUTE, CURTIME(), b.start_time) <= ?
             ORDER BY b.start_time ASC",
            [$horizon]
        );

        $out = [];
        foreach ($rows as $r) {
            $name  = $r['cust_linked_name'] ?? $r['customer_name'] ?? 'Valued Guest';
            $phone = Customer::normalizePhone($r['cust_linked_phone'] ?? $r['customer_phone'] ?? '');
            $time  = date('g:i A', strtotime($r['start_time']));

            $message = str_replace(
                ['{name}', '{club}', '{date}', '{time}', '{table}'],
                [$name, self::clubName(), date('D, j M', strtotime($r['booking_date'])), $time, $r['table_number'] ?? ''],
                self::setting('booking_reminder_template', 'Hi {name}! Reminder: your booking at {club} is today at {time} on Table {table}.')
            );

            $out[] = [
                'id'      => (int) $r['id'],
                'name'    => $name,
                'phone'   => $phone,
                'table'   => $r['table_number'] ?? '',
                'date'    => $r['booking_date'],
                'time'    => $time,
                'message' => $message,
                'waLink'  => Customer::whatsappLink($phone, $message),
            ];
        }

        return $out;
    }

    /**
     * Customers with an outstanding balance not reminded within 24h.
     */
    public static function outstandingCandidates(): array
    {
        $rows = Database::query(
            "SELECT id, name, phone, outstanding_balance, last_visit_at, last_reminded_at
             FROM customers
             WHERE status = 'active'
               AND outstanding_balance > 0
               AND (last_reminded_at IS NULL OR last_reminded_at < DATE_SUB(NOW(), INTERVAL 1 DAY))
             ORDER BY outstanding_balance DESC"
        );

        $out = [];
        foreach ($rows as $r) {
            $amount = number_format((float) $r['outstanding_balance']);

            $message = str_replace(
                ['{name}', '{club}', '{currency}', '{amount}'],
                [$r['name'], self::clubName(), self::currency(), $amount],
                self::setting('outstanding_reminder_template', 'Hi {name}! You have an outstanding balance of {currency} {amount} at {club}.')
            );

            $out[] = [
                'customerId' => (int) $r['id'],
                'name'       => $r['name'],
                'phone'      => $r['phone'] ?? '',
                'balance'    => (float) $r['outstanding_balance'],
                'lastVisit'  => $r['last_visit_at'],
                'lastRemindedAt' => $r['last_reminded_at'],
                'message'    => $message,
                'waLink'     => Customer::whatsappLink($r['phone'], $message),
            ];
        }

        return $out;
    }

    /**
     * Combined view for the in-app reminders center (does not mutate).
     */
    public static function pending(): array
    {
        return [
            'bookings'    => self::bookingCandidates(),
            'outstanding' => self::outstandingCandidates(),
        ];
    }

    /**
     * Cron sweep: prepare a dated batch file and mark candidates as reminded.
     * Returns the number of reminders written.
     */
    public static function sweep(bool $commit = true): int
    {
        if (!self::enabled() || $commit === false) {
            return self::pendingCount();
        }

        $pending = self::pending();
        $lines   = [];
        $count   = 0;

        foreach ($pending['bookings'] as $b) {
            $lines[] = $b['phone'] . "  " . $b['message'] . "  " . $b['waLink'];
            Database::execute('UPDATE bookings SET reminder_sent_at = NOW() WHERE id = ?', [$b['id']]);
            $count++;
        }

        foreach ($pending['outstanding'] as $c) {
            $lines[] = $c['phone'] . "  " . $c['message'] . "  " . $c['waLink'];
            Database::execute('UPDATE customers SET last_reminded_at = NOW() WHERE id = ?', [$c['customerId']]);
            $count++;
        }

        if ($lines !== []) {
            $dir = self::storageDir();
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $file = $dir . '/reminders-' . date('Ymd-Hi') . '.txt';
            file_put_contents($file, "Asif Snooker Club — WhatsApp reminder batch\n" .
                                     "Generated: " . date('Y-m-d H:i:s') . "\n" .
                                     str_repeat('-', 72) . "\n" .
                                     implode("\n", $lines) . "\n");
        }

        return $count;
    }

    private static function pendingCount(): int
    {
        $p = self::pending();
        return count($p['bookings']) + count($p['outstanding']);
    }

    public static function storageDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/reminders';
    }
}