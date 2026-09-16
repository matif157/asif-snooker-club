<?php

declare(strict_types=1);

/**
 * CLI WhatsApp reminder sweep (cron-friendly).
 * Usage:
 *   php database/remind.php          # dry-run: show what would be reminded now
 *   php database/remind.php --run    # write batch file + mark sent (dedup)
 *
 * Tip for cron (hourly, 8:00–22:00):
 *   0 8-22 * * * /usr/bin/php /path/to/asif-snooker-club/database/remind.php --run
 *
 * Candidates are also configurable under Settings → Reminders & Notifications.
 */

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';
load_env(ROOT_PATH . '/.env');

use App\Services\ReminderService;

$run = in_array('--run', $argv, true);

if (!ReminderService::enabled()) {
    echo "Reminders are disabled (Settings → Reminders & Notifications).\n";
    exit(0);
}

$horizon = ReminderService::horizonMinutes();
$pending = ReminderService::pending();

echo "Asif Snooker Club — WhatsApp reminders (horizon: {$horizon} min)\n";
echo str_repeat('-', 60) . "\n";

$count = 0;

foreach ($pending['bookings'] as $b) {
    echo "[booking] {$b['name']} ({$b['phone']}) Table {$b['table']} at {$b['time']}\n";
    echo "    {$b['message']}\n    {$b['waLink']}\n";
    $count++;
}

foreach ($pending['outstanding'] as $c) {
    echo "[outstanding] {$c['name']} ({$c['phone']}) Rs " . number_format($c['balance']) . "\n";
    echo "    {$c['message']}\n    {$c['waLink']}\n";
    $count++;
}

if ($run) {
    $written = ReminderService::sweep();
    echo str_repeat('-', 60) . "\n";
    echo "Committed: {$written} reminder(s) written to storage/reminders/.\n";
} else {
    echo str_repeat('-', 60) . "\n";
    echo "{$count} reminder(s) due. Re-run with --run to write the batch and prevent duplicates.\n";
}

exit(0);