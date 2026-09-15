<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Booking;
use App\Models\Table as TableModel;

class BookingController extends Controller
{
    public function index(): void
    {
        $date = Request::get('date', date('Y-m-d'));
        $bookings = Booking::forDate($date);
        $tables   = TableModel::activeTables();
        $upcoming = Booking::upcoming(20);

        $this->view('bookings/index', [
            'bookings'  => $bookings,
            'tables'    => $tables,
            'upcoming'  => $upcoming,
            'selectedDate' => $date,
        ]);
    }

    public function store(): void
    {
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

        if (Request::isAjax()) {
            Response::success(['id' => $id], 'Booking created');
        }
        Response::redirect('/bookings');
    }

    public function updateStatus(int $id): void
    {
        $booking = Booking::find($id);
        if (!$booking) {
            Response::error('Booking not found', 404);
        }

        $status = Request::input('status');
        $validTransitions = [
            'requested'  => ['confirmed', 'cancelled', 'expired'],
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