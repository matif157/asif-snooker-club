<?php

declare(strict_types=1);

/**
 * Tidy demo data seed for Asif Snooker Club CRM.
 * Usage:
 *   php database/seed_demo.php
 *
 * Safe to re-run: wipes + recreates a coherent demo dataset
 * (sessions, payments, expenses, bookings, tournaments, junk customers)
 * while keeping owners, roles, tables, cameras and core customers.
 */

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/vendor/autoload.php';

load_env(ROOT_PATH . '/.env');

use App\Core\Database;

$db = Database::connection();

$now = time();
$ts = static fn (int $offsetSeconds): string => date('Y-m-d H:i:s', $now + $offsetSeconds);

echo "Seeding demo data...\n";

// ---------------------------------------------------------------
// 1) Wipe volatile demo tables
// ---------------------------------------------------------------
$db->exec('DELETE FROM tournament_matches');
$db->exec('DELETE FROM tournament_players');
$db->exec('DELETE FROM tournaments');
$db->exec('DELETE FROM payments');
$db->exec('DELETE FROM sessions');
$db->exec('DELETE FROM bookings');
$db->exec('DELETE FROM expenses');

// Wipe junk customers (id 5 "Client Test", id 11 "Imran Khan")
$db->exec('DELETE FROM customers WHERE id IN (5, 11)');

// ---------------------------------------------------------------
// 2) Customers
// ---------------------------------------------------------------
$pin = $db->query('SELECT portal_pin FROM customers WHERE id = 1')->fetchColumn() ?: '';

$customers = [
    ['Hamza Iqbal',    '+923331234567', 'regular',   $pin],
    ['Usman Sheikh',   '+923457112233', 'vip',       $pin],
    ['Bilal Hussain',  '+923004455667', 'regular',   null],
    ['Raza Qureshi',   '+923336677889', 'tournament', null],
];

$custStmt = $db->prepare(
    'INSERT INTO customers (name, phone, whatsapp, category, status, portal_pin)
     VALUES (?, ?, ?, ?, "active", ?)
     ON DUPLICATE KEY UPDATE name = VALUES(name), category = VALUES(category), portal_pin = COALESCE(VALUES(portal_pin), portal_pin)'
);

foreach ($customers as [$name, $phone, $category, $pinHash]) {
    $custStmt->execute([$name, $phone, $phone, $category, $pinHash]);
}

$ids = [
    'ali'    => 1,
    'sara'   => 3,
    'ahmed'  => 4,
    'hamza'  => (int) $db->query("SELECT id FROM customers WHERE phone = '+923331234567'")->fetchColumn(),
    'usman'  => (int) $db->query("SELECT id FROM customers WHERE phone = '+923457112233'")->fetchColumn(),
    'bilal'  => (int) $db->query("SELECT id FROM customers WHERE phone = '+923004455667'")->fetchColumn(),
    'raza'   => (int) $db->query("SELECT id FROM customers WHERE phone = '+923336677889'")->fetchColumn(),
];

// ---------------------------------------------------------------
// 3) Sessions
// ---------------------------------------------------------------
// 2 active right now + 5 completed (yesterday/tonight), with fish store
$sessions = [
    // [table_id, customer_id, players, startOffset, endOffset, rate_type, rate, amount, payment_status]
    [5, $ids['usman'], 2, -25 * 60, null, 'hourly', 300.00, 0.00, 'unpaid'],   // active now
    [1, $ids['hamza'], 2, -10 * 60, null, 'hourly', 250.00, 0.00, 'unpaid'],   // active now
    [2, $ids['ali'],   2, -25 * 3600, -23 * 3600, 'hourly', 300.00, 600.00, 'paid'],
    [3, $ids['sara'],  2, -26 * 3600, -24 * 3600 + 1800, 'hourly', 300.00, 450.00, 'paid'],
    [4, $ids['ahmed'], 1, -27 * 3600, -26 * 3600, 'frame', 100.00, 300.00, 'paid'],
    [6, $ids['bilal'], 2, -23 * 3600, -22 * 3600, 'night', 500.00, 500.00, 'paid'],
    [7, null,          4, -25 * 3600 + 3600, -24 * 3600, 'hourly', 400.00, 400.00, 'partial'],
];

$sesStmt = $db->prepare(
    'INSERT INTO sessions (table_id, customer_id, players_count, start_time, end_time,
                           rate_type, rate, amount, payment_status, payment_method, status, staff_id)
     VALUES (:table_id, :customer_id, :players, :start, :end, :rate_type, :rate, :amount,
             :payment_status, :method, :status, 1)'
);

$sessionIds = [];
foreach ($sessions as [$table, $customer, $players, $startOff, $endOff, $rateType, $rate, $amount, $pay]) {
    $isActive = $endOff === null;
    $sesStmt->execute([
        ':table_id'       => $table,
        ':customer_id'    => $customer,
        ':players'        => $players,
        ':start'          => $ts($startOff),
        ':end'            => $endOff === null ? null : $ts($endOff),
        ':rate_type'      => $rateType,
        ':rate'           => $rate,
        ':amount'         => $amount,
        ':payment_status' => $pay,
        ':method'         => $pay === 'paid' ? 'cash' : null,
        ':status'         => $isActive ? 'active' : 'completed',
    ]);
    $sessionIds[] = (int) $db->lastInsertId();
}

// ---------------------------------------------------------------
// 4) Payments
// ---------------------------------------------------------------
$payStmt = $db->prepare(
    'INSERT INTO payments (session_id, booking_id, amount, method, status)
     VALUES (:session_id, :booking_id, :amount, :method, :status)'
);

// Completed paid sessions (session history)
$payStmt->execute([':session_id' => $sessionIds[2], ':booking_id' => null, ':amount' => 600.00, ':method' => 'cash',     ':status' => 'paid']);
$payStmt->execute([':session_id' => $sessionIds[3], ':booking_id' => null, ':amount' => 450.00, ':method' => 'jazzcash', ':status' => 'paid']);
$payStmt->execute([':session_id' => $sessionIds[4], ':booking_id' => null, ':amount' => 300.00, ':method' => 'cash',     ':status' => 'paid']);
$payStmt->execute([':session_id' => $sessionIds[5], ':booking_id' => null, ':amount' => 500.00, ':method' => 'cash',     ':status' => 'paid']);
// Partial payment on the Balcony VIP walk-in
$payStmt->execute([':session_id' => $sessionIds[6], ':booking_id' => null, ':amount' => 200.00, ':method' => 'cash',     ':status' => 'paid']);

// ---------------------------------------------------------------
// 5) Bookings
// ---------------------------------------------------------------
$bookStmt = $db->prepare(
    'INSERT INTO bookings (table_id, customer_id, customer_name, customer_phone, booking_date,
                           start_time, end_time, players_count, status, notes, created_by)
     VALUES (:table_id, :customer_id, :customer_name, :customer_phone, :booking_date,
             :start_time, :end_time, :players_count, :status, :notes, :created_by)'
);

// Requested (portal) — lights the approval alert on the dashboard
$bookStmt->execute([
    ':table_id' => 4, ':customer_id' => $ids['hamza'], ':customer_name' => 'Hamza Iqbal',
    ':customer_phone' => '+923331234567', ':booking_date' => date('Y-m-d', $now),
    ':start_time' => '19:00', ':end_time' => '21:00', ':players_count' => 2,
    ':status' => 'requested', ':notes' => 'Booked via member portal', ':created_by' => null,
]);
$requestedBooking = (int) $db->lastInsertId();

// Confirmed tonight — Ali Raza
$bookStmt->execute([
    ':table_id' => 6, ':customer_id' => $ids['ali'], ':customer_name' => 'Ali Raza',
    ':customer_phone' => '+923001234567', ':booking_date' => date('Y-m-d', $now),
    ':start_time' => '21:00', ':end_time' => '22:30', ':players_count' => 2,
    ':status' => 'confirmed', ':notes' => null, ':created_by' => 1,
]);

// Arrived now — Usman Sheikh (matches the active session)
$bookStmt->execute([
    ':table_id' => 5, ':customer_id' => $ids['usman'], ':customer_name' => 'Usman Sheikh',
    ':customer_phone' => '+923457112233', ':booking_date' => date('Y-m-d', $now),
    ':start_time' => date('H:i', $now - 20 * 60), ':end_time' => date('H:i', $now + 60 * 60), ':players_count' => 2,
    ':status' => 'arrived', ':notes' => null, ':created_by' => 1,
]);

// Upcoming portal booking — tomorrow
$bookStmt->execute([
    ':table_id' => 2, ':customer_id' => $ids['ali'], ':customer_name' => 'Ali Raza',
    ':customer_phone' => '+923001234567', ':booking_date' => date('Y-m-d', $now + 86400),
    ':start_time' => '16:00', ':end_time' => '17:00', ':players_count' => 2,
    ':status' => 'confirmed', ':notes' => 'Booked via member portal', ':created_by' => null,
]);
$portalBooking = (int) $db->lastInsertId();

// Completed yesterday — Sara Ali (shows a Paid badge)
$bookStmt->execute([
    ':table_id' => 1, ':customer_id' => $ids['sara'], ':customer_name' => 'Sara Ali',
    ':customer_phone' => '+923451234567', ':booking_date' => date('Y-m-d', $now - 86400),
    ':start_time' => '18:00', ':end_time' => '19:30', ':players_count' => 2,
    ':status' => 'completed', ':notes' => null, ':created_by' => 1,
]);
$completedBooking = (int) $db->lastInsertId();

// Payments tied to bookings (advance / paid)
$payStmt->execute([':session_id' => null, ':booking_id' => $requestedBooking, ':amount' => 0.00,   ':method' => 'cash',     ':status' => 'paid']);
$payStmt->execute([':session_id' => null, ':booking_id' => $portalBooking,   ':amount' => 500.00,   ':method' => 'jazzcash', ':status' => 'paid']);
$payStmt->execute([':session_id' => null, ':booking_id' => $completedBooking, ':amount' => 450.00,  ':method' => 'cash',     ':status' => 'paid']);

// ---------------------------------------------------------------
// 6) Expenses
// ---------------------------------------------------------------
$expStmt = $db->prepare(
    'INSERT INTO expenses (category, amount, expense_date, vendor, description, status, approved_by, created_by)
     VALUES (:category, :amount, :date, :vendor, :description, "approved", 1, 1)'
);

$expenses = [
    ['electricity', 2500.00, 'WAPDA',         'Monthly K-Electric bill'],
    ['rent',       40000.00, 'Property Owner', 'September club rent'],
    ['refreshments', 1500.00, 'Punjab Caterers', 'Evening refreshments for the event'],
    ['maintenance', 800.00,  'Local Hardware', 'Table cloth, chalks & accessories'],
];

foreach ($expenses as [$category, $amount, $vendor, $description]) {
    $expStmt->execute([
        ':category' => $category,
        ':amount'   => $amount,
        ':date'     => date('Y-m-d', $now),
        ':vendor'   => $vendor,
        ':description' => $description,
    ]);
}

// One expense logged by the counter — awaiting owner/finance approval
$db->prepare(
    'INSERT INTO expenses (category, amount, expense_date, vendor, description, status, created_by)
     VALUES (:category, :amount, :date, :vendor, :description, "pending", 3)'
)->execute([
    ':category' => 'supplies',
    ':amount'   => 420.00,
    ':date'     => date('Y-m-d', $now),
    ':vendor'   => 'Paper World',
    ':description' => 'Ball polish, chalk & score sheets',
]);

// Cameras — give the demo streams stable go2rtc stream names
// (rtsp_url is left to the operator; the go2rtc config generator
//  picks cameras that have an RTSP source).
$db->exec("UPDATE cameras SET stream_name = 'main_hall' WHERE id = 1");
$db->exec("UPDATE cameras SET stream_name = 'entrance' WHERE id = 2 AND stream_name IS NULL");

// CCTV defaults (only when not already configured)
$db->exec("INSERT INTO settings (`key`, `value`, `group`) VALUES ('cctv_server_url', 'http://127.0.0.1:1984', 'cctv')
          ON DUPLICATE KEY UPDATE `key` = `key`");
$db->exec("INSERT INTO settings (`key`, `value`, `group`) VALUES ('cctv_stream_mode', 'img', 'cctv')
          ON DUPLICATE KEY UPDATE `key` = `key`");

// Expense budgets (only when not already configured)
$demoBudgets = json_encode([
    'rent'         => 45000,
    'electricity'  => 3000,
    'refreshments' => 2500,
    'maintenance'  => 2000,
    'supplies'     => 1000,
]);
$db->exec(
    "INSERT IGNORE INTO settings (`key`, `value`, `group`)
     SELECT 'expense_budgets', '" . str_replace("'", "''", $demoBudgets) . "', 'finance'"
);

// ---------------------------------------------------------------
// Summary
// ---------------------------------------------------------------
$sum = static function (string $table): int {
    return (int) Database::connection()->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
};

echo sprintf(
    "Done. customers=%d tables=%d sessions=%d (2 active) bookings=%d expenses=%d payments=%d tournaments=%d\n",
    $sum('customers'),
    $sum('tables'),
    $sum('sessions'),
    $sum('bookings'),
    $sum('expenses'),
    $sum('payments'),
    $sum('tournaments')
);
echo "Portal demo: Hamza Iqbal (requested booking → approve), Ali Raza PIN 1234 (book twice), Usman Sheikh (active).\n";