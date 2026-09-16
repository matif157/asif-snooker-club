<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\Table as TableModel;
use App\Services\CsvService;

class BookingController extends Controller
{
    public function index(): void
    {
        if (!user_can('bookings.view')) {
            $this->error('You do not have permission to view bookings.', 403);
        }

        Booking::expirePast();
        Booking::markNoShows();

        $date = Request::get('date', date('Y-m-d'));
        $bookings = Booking::forDate($date);
        $tables   = TableModel::activeTables();
        $upcoming = Booking::upcoming(20);

        // Payments attached to today's bookings (advances / desk settlements)
        $bookingsPaid = [];
        if ($bookings !== []) {
            $ids = implode(',', array_map('intval', array_column($bookings, 'id')));
            $payments = Database::query(
                "SELECT booking_id, COALESCE(SUM(amount), 0) AS total
                 FROM payments
                 WHERE booking_id IN ({$ids}) AND status = 'paid'
                 GROUP BY booking_id"
            );
            foreach ($payments as $p) {
                $bookingsPaid[(int) $p['booking_id']] = (float) $p['total'];
            }
        }

        $this->view('bookings/index', [
            'bookings'  => $bookings,
            'tables'    => $tables,
            'upcoming'  => $upcoming,
            'bookingsPaid' => $bookingsPaid,
            'selectedDate' => $date,
        ]);
    }

    public function export(): void
    {
        if (!user_can('bookings.view')) {
            $this->error('You do not have permission to export bookings.', 403);
        }

        Booking::expirePast();

        $from = Request::get('from', Request::get('date', date('Y-m-d')));
        $to   = Request::get('to', $from);

        $rows = Database::query(
            "SELECT b.*,
                    t.number AS table_number,
                    t.name AS table_name,
                    COALESCE(c.name, b.customer_name) AS customer_name,
                    COALESCE(c.phone, b.customer_phone) AS customer_phone,
                    COALESCE(SUM(p.amount), 0) AS advance_paid
             FROM bookings b
             JOIN tables t ON t.id = b.table_id
             LEFT JOIN customers c ON c.id = b.customer_id
             LEFT JOIN payments p ON p.booking_id = b.id AND p.status = 'paid'
             WHERE b.booking_date BETWEEN ? AND ?
             GROUP BY b.id
             ORDER BY b.booking_date DESC, b.start_time ASC",
            [$from, $to]
        );

        CsvService::sendHeaders('bookings-' . $from . '-to-' . $to . '.csv');
        CsvService::download('', [
            'id'             => '#',
            'booking_date'   => 'Date',
            'start_time'     => 'From',
            'end_time'       => 'To',
            'table_number'   => 'Table No',
            'table_name'     => 'Table Name',
            'customer_name'  => 'Customer',
            'customer_phone' => 'Phone',
            'players_count'  => 'Players',
            'status'         => 'Status',
            'advance_paid'   => 'Advance Paid',
            'source'         => 'Source',
            'notes'          => 'Notes',
        ], $rows, [
            'source'    => fn($r) => ($r['created_by'] ?? null) === null ? 'Member Portal' : 'Staff',
            'status'    => fn($r) => ucfirst((string) $r['status']),
            'advance_paid' => fn($r) => (float) $r['advance_paid'],
        ]);
    }

    public function calendar(): void
    {
        if (!user_can('bookings.view')) {
            $this->error('You do not have permission to view bookings.', 403);
        }

        Booking::expirePast();

        $month = Request::get('month');
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $month) ? $month : date('Y-m');

        [$year, $mon] = array_map('intval', explode('-', $month));
        $firstDay  = sprintf('%04d-%02d-01', $year, $mon);
        $daysInMon = (int) date('t', strtotime($firstDay));
        $lastDay   = sprintf('%04d-%02d-%02d', $year, $mon, $daysInMon);

        // ISO-8601: Monday = 1 ... Sunday = 7
        $firstDow  = (int) date('N', strtotime($firstDay));

        $bookings = Booking::forMonth($firstDay, $lastDay);
        $byDay = [];
        foreach ($bookings as $b) {
            $byDay[$b['booking_date']][] = $b;
        }

        // Build the 6-row grid (42 cells covers every month).
        // Day 1 lands at column (N-1) where N=1 for Monday.
        $cells = [];
        $cursor = 2 - $firstDow;
        for ($i = 0; $i < 42; $i++) {
            $cells[] = $cursor;
            $cursor++;
        }

        $this->view('bookings/calendar', [
            'month'      => $month,
            'display'    => date('F Y', strtotime($firstDay)),
            'prevMonth'  => date('Y-m', strtotime($firstDay . ' -1 month')),
            'nextMonth'  => date('Y-m', strtotime($firstDay . ' +1 month')),
            'cells'      => $cells,
            'daysInMonth'=> $daysInMon,
            'byDay'      => $byDay,
            'today'      => date('Y-m-d'),
        ]);
    }

    public function store(): void
    {
        if (!user_can('bookings.manage')) {
            $this->error('You do not have permission to manage bookings.', 403);
        }

        Booking::expirePast();

        $data = Request::all();
        $errors = $this->validate($data, [
            'table_id'      => 'required|numeric',
            'booking_date'  => 'required',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'customer_name' => 'required',
        ]);

        if (!empty($errors)) {
            if (Request::isAjax()) {
                Response::error('Validation failed', 422, $errors);
            }
            Response::redirect('/bookings');
        }

        $tableId  = (int) $data['table_id'];
        $date     = $data['booking_date'];
        $start    = $data['start_time'];
        $end      = $data['end_time'];

        if (!Booking::isTableFree($tableId, $date, $start, $end)) {
            $msg = 'Table is not available for the selected time slot.';
            if (Request::isAjax()) { Response::error($msg); }
            Response::redirect('/bookings');
        }

        $customerId = (int) ($data['customer_id'] ?? 0);

        $id = Booking::create([
            'table_id'       => $tableId,
            'customer_id'    => $customerId > 0 ? $customerId : null,
            'customer_name'  => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'booking_date'   => $date,
            'start_time'     => $start,
            'end_time'       => $end,
            'players_count'  => max(1, (int) ($data['players_count'] ?? 2)),
            'status'         => 'requested',
            'notes'          => $data['notes'] ?? null,
            'created_by'     => current_user()?->id ?? null,
        ]);

        $this->recordAdvance($id, $customerId > 0 ? $customerId : null, $data);

        if (Request::isAjax()) {
            Response::success(['id' => $id], 'Booking created');
        }
        Response::redirect('/bookings');
    }

    /**
     * Optional advance / deposit taken while creating a booking. Records a
     * payment row against the booking so it can be settled or topped up later.
     */
    private function recordAdvance(int $bookingId, ?int $customerId, array $data): void
    {
        $amount = (float) ($data['advance_amount'] ?? 0);
        if ($amount <= 0) {
            return;
        }

        $method = (string) ($data['advance_method'] ?? 'cash');
        if (!in_array($method, ['cash', 'jazzcash', 'bank_transfer', 'card', 'other'], true)) {
            return;
        }

        Database::insert(
            "INSERT INTO payments (booking_id, customer_id, amount, method, status, transaction_ref, notes, paid_at, accepted_by)
             VALUES (?, ?, ?, ?, 'paid', ?, ?, NOW(), ?)",
            [
                $bookingId,
                $customerId,
                $amount,
                $method,
                $data['transaction_ref'] ?? null,
                $data['advance_notes'] ?? 'Advance payment at booking',
                current_user()?->id ?? null,
            ]
        );
    }

    /**
     * Record a payment against an existing booking (desk/full settlement later).
     */
    public function pay(int $id): void
    {
        if (!user_can('payments.manage')) {
            $this->error('You do not have permission to record payments.', 403);
        }

        $booking = Booking::find($id);
        if (!$booking) {
            Response::error('Booking not found', 404);
        }

        $amount = (float) (Request::input('amount') ?? 0);
        $method = (string) (Request::input('method') ?? 'cash');

        if ($amount <= 0) {
            Response::error('Amount must be greater than zero.');
        }
        if (!in_array($method, ['cash', 'jazzcash', 'bank_transfer', 'card', 'other'], true)) {
            Response::error('Invalid payment method.');
        }

        Database::insert(
            "INSERT INTO payments (booking_id, customer_id, amount, method, status, transaction_ref, notes, paid_at, accepted_by)
             VALUES (?, ?, ?, ?, 'paid', ?, ?, NOW(), ?)",
            [
                $id,
                $booking->customer_id ? (int) $booking->customer_id : null,
                $amount,
                $method,
                Request::input('transaction_ref') ?? null,
                Request::input('notes') ?? 'Payment for booking',
                current_user()?->id ?? null,
            ]
        );

        if (Request::isAjax()) {
            Response::success([], 'Payment recorded');
        }
        flash('success', 'Payment of Rs ' . number_format($amount) . ' recorded for the booking.');
        Response::redirect('/bookings');
    }

    public function updateStatus(int $id): void
    {
        if (!user_can('bookings.manage')) {
            $this->error('You do not have permission to manage bookings.', 403);
        }

        $booking = Booking::find($id);
        if (!$booking) {
            Response::error('Booking not found', 404);
        }

        $status = Request::input('status');
        $validTransitions = [
            'requested'  => ['confirmed', 'arrived', 'cancelled', 'expired'],
            'confirmed'  => ['arrived', 'cancelled', 'expired'],
            'arrived'    => ['active', 'cancelled', 'no_show'],
            'active'     => ['completed'],
            'completed'  => [],
            'cancelled'  => [],
            'no_show'    => [],
            'expired'    => [],
        ];

        if (!in_array($status, $validTransitions[$booking->status] ?? [])) {
            Response::error("Cannot change from '{$booking->status}' to '{$status}'");
        }

        $booking->update(['status' => $status]);

        if (Request::isAjax()) {
            Response::success(['status' => $status], 'Booking updated');
        }
        Response::redirect('/bookings');
    }
}