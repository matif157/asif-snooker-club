<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Table as TableModel;

/**
 * Public self-service member portal.
 *
 * Two states:
 *  - Logged out: phone + 4-digit PIN login (PIN is issued by the club).
 *  - Logged in:   balance, upcoming bookings, recent sessions/payments,
 *                 and self-service table booking.
 */
class PortalController extends Controller
{
    private const SESSION_KEY = 'portal_customer_id';

    public function index(): void
    {
        $customerId = (int) ($_SESSION[self::SESSION_KEY] ?? 0);
        $customer   = $customerId > 0 ? Customer::find($customerId)?->toArray() : null;

        if ($customer === null) {
            $this->view('portal/index', [
                'authed'    => false,
                'customer'  => null,
                'sessions'  => [],
                'payments'  => [],
                'bookings'  => [],
                'tables'    => TableModel::activeTables(),
                'presetPhone' => (string) ($_GET['phone'] ?? ''),
            ], 'portal');
            return;
        }

        $sessions = Database::query(
            "SELECT s.*, t.number AS table_number, t.name AS table_name
             FROM sessions s
             JOIN tables t ON t.id = s.table_id
             WHERE s.customer_id = ?
             ORDER BY s.id DESC LIMIT 6",
            [$customerId]
        );

        $payments = Database::query(
            "SELECT * FROM payments
             WHERE customer_id = ?
             ORDER BY id DESC LIMIT 6",
            [$customerId]
        );

        $bookings = Database::query(
            "SELECT b.*, t.number AS table_number, t.name AS table_name
             FROM bookings b
             JOIN tables t ON t.id = b.table_id
             WHERE b.customer_id = ? AND b.status IN ('requested','confirmed','arrived')
             ORDER BY b.booking_date ASC, b.start_time ASC
             LIMIT 6",
            [$customerId]
        );

        $this->view('portal/index', [
            'authed'    => true,
            'customer'  => $customer,
            'sessions'  => $sessions,
            'payments'  => $payments,
            'bookings'  => $bookings,
            'tables'    => TableModel::activeTables(),
            'presetPhone' => '',
        ], 'portal');
    }

    public function login(): void
    {
        $phone = trim((string) (Request::input('phone') ?? ''));
        $pin   = trim((string) (Request::input('pin') ?? ''));

        $customer = Customer::findByPhone($phone);

        if ($customer === null || empty($customer['portal_pin'])) {
            $this->flashLoginError('No portal account found for that number. Ask the club desk to activate yours.');
            Response::redirect('/portal');
        }

        if (!Customer::verifyPortalPin((int) $customer['id'], $pin)) {
            $this->flashLoginError('That PIN does not match. Please try again, or get a new PIN from the club desk.');
            Response::redirect('/portal?error=1');
        }

        $_SESSION[self::SESSION_KEY] = (int) $customer['id'];
        Response::redirect('/portal');
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        Response::redirect('/portal');
    }

    public function book(): void
    {
        $customerId = (int) ($_SESSION[self::SESSION_KEY] ?? 0);
        $customer = $customerId > 0 ? Customer::find($customerId)?->toArray() : null;
        if ($customer === null) {
            Response::redirect('/portal');
        }

        Booking::expirePast();

        $tableId = (int) (Request::input('table_id') ?? 0);
        $date    = (string) (Request::input('booking_date') ?? '');
        $start   = (string) (Request::input('start_time') ?? '');
        $end     = (string) (Request::input('end_time') ?? '');

        $errors = $this->validate(Request::all(), [
            'table_id'     => 'required|numeric',
            'booking_date' => 'required',
            'start_time'   => 'required',
            'end_time'     => 'required',
        ]);

        if (empty($errors) && strtotime($date . ' ' . $start) !== false && strtotime($date . ' 00:00:00') < strtotime('today')) {
            $errors['booking_date'] = 'Bookings must be for today or a future date.';
        }
        if (empty($errors) && strtotime($start) >= strtotime($end)) {
            $errors['end_time'] = 'End time must be after the start time.';
        }

        if (!empty($errors)) {
            flash('error', 'Please check the booking details: ' . implode(', ', array_values($errors)));
            Response::redirect('/portal#book');
        }

        if (!Booking::isTableFree($tableId, $date, $start, $end)) {
            flash('error', 'That table is not available for the selected slot. Please pick another table or time.');
            Response::redirect('/portal#book');
        }

        Booking::create([
            'table_id'       => $tableId,
            'customer_id'    => $customerId,
            'customer_name'  => $customer['name'],
            'customer_phone' => $customer['phone'],
            'booking_date'   => $date,
            'start_time'     => $start,
            'end_time'       => $end,
            'players_count'  => max(2, (int) (Request::input('players_count') ?? 2)),
            'status'         => 'requested',
            'notes'          => 'Booked via member portal',
            'created_by'     => null,
        ]);

        flash('success', 'Booking requested! The club will confirm it shortly — you can also call or WhatsApp to speed it up.');
        Response::redirect('/portal#book');
    }

    private function flashLoginError(string $message): void
    {
        flash('error', $message);
    }
}