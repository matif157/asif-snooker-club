<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\ClubSession;
use App\Models\Payment;
use App\Models\Customer;
use App\Services\CsvService;
use App\Services\SettingsService;

class PaymentController extends Controller
{
    public function index(): void
    {
        if (!user_can('payments.view')) {
            $this->error('You do not have permission to view payments.', 403);
        }

        $payments    = Payment::recent(50);
        $outstanding = Payment::outstandingCustomers(10);
        $todayRev    = Payment::todayRevenueByMethod();

        $this->view('payments/index', [
            'payments'    => $payments,
            'outstanding' => $outstanding,
            'todayRev'    => $todayRev,
        ]);
    }

    public function export(): void
    {
        if (!user_can('payments.view')) {
            $this->error('You do not have permission to export payments.', 403);
        }

        $rows = Database::query(
            "SELECT p.*,
                    c.name AS customer_name,
                    t.number AS table_number,
                    u.name AS acceptor_name
             FROM payments p
             LEFT JOIN customers c ON c.id = p.customer_id
             LEFT JOIN sessions s ON s.id = p.session_id
             LEFT JOIN tables t ON t.id = s.table_id
             LEFT JOIN users u ON u.id = p.accepted_by
             ORDER BY p.id DESC
             LIMIT 10000"
        );

        CsvService::sendHeaders('payments-' . date('Y-m-d') . '.csv');
        CsvService::download('', [
            'id'              => '#',
            'paid_at'         => 'Date/Time',
            'customer_name'   => 'Customer',
            'session_id'      => 'Session ID',
            'booking_id'      => 'Booking ID',
            'table_number'    => 'Table',
            'amount'          => 'Amount',
            'method'          => 'Method',
            'transaction_ref' => 'Transaction Ref',
            'status'          => 'Status',
            'acceptor_name'   => 'Accepted By',
            'notes'           => 'Notes',
        ], $rows, [
            'paid_at' => fn($r) => date('Y-m-d H:i', strtotime((string) ($r['paid_at'] ?? $r['created_at']))),
            'method'  => fn($r) => strtoupper((string) $r['method']),
            'status'  => fn($r) => ucfirst((string) $r['status']),
        ]);
    }

    public function store(): void
    {
        if (!user_can('payments.manage')) {
            $this->error('You do not have permission to record payments.', 403);
        }

        $data = Request::all();
        $errors = $this->validate($data, [
            'amount' => 'required|numeric',
            'method' => 'required',
        ]);

        if (!empty($errors)) {
            Response::redirect('/payments');
        }

        $customerId = (int) ($data['customer_id'] ?? 0);
        $sessionId  = (int) ($data['session_id'] ?? 0);

        $paymentId = Payment::create([
            'session_id'     => $sessionId > 0 ? $sessionId : null,
            'customer_id'    => $customerId > 0 ? $customerId : null,
            'amount'         => (float) $data['amount'],
            'method'         => $data['method'],
            'status'         => 'paid',
            'transaction_ref'=> $data['transaction_ref'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'paid_at'        => date('Y-m-d H:i:s'),
            'accepted_by'    => current_user()?->id ?? null,
        ]);

        // Update session payment status if linked
        if ($sessionId > 0) {
            $session = ClubSession::find($sessionId);
            if ($session) {
                $paidRow = Database::fetchOne(
                    "SELECT COALESCE(SUM(amount),0) AS t FROM payments
                     WHERE session_id = ? AND status = 'paid'",
                    [$sessionId]
                );
                $paidTotal = (float) ($paidRow['t'] ?? 0);
                $session->update([
                    'payment_status' => $paidTotal >= (float) $session->amount ? 'paid' : 'partial',
                    'payment_method' => $data['method'],
                ]);
            }
        }

        // Update customer outstanding if linked
        if ($customerId > 0 && (float) $data['amount'] > 0) {
            Database::execute(
                'UPDATE customers SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE id = ?',
                [(float) $data['amount'], $customerId]
            );
        }

        \App\Services\AuditService::log('payment_received', 'payment', $paymentId, null, [
            'customer' => $customerId ?: null,
            'session'  => $sessionId ?: null,
            'amount'   => (float) $data['amount'],
            'method'   => $data['method'],
        ]);

        if (Request::isAjax()) {
            Response::success(['id' => $paymentId], 'Payment recorded');
        }
        Response::redirect('/payments');
    }

    public function receipt(int $id): void
    {
        if (!user_can('payments.view')) {
            $this->error('You do not have permission to view payments.', 403);
        }

        $payment = Payment::withDetails($id);
        if (!$payment) {
            Response::error('Payment not found', 404);
        }

        $settings = SettingsService::all();

        $this->view('payments/receipt', [
            'p'        => $payment,
            'settings' => $settings,
            'operator' => $payment['acceptor_name'] ?? (current_user()?->name ?? ''),
        ], 'blank');
    }

    public function apiPay(int $id): void
    {
        if (!user_can('payments.manage')) {
            $this->error('You do not have permission to record payments.', 403);
        }

        $session = ClubSession::find($id);
        if (!$session) {
            Response::error('Session not found', 404);
        }

        $amount = (float) (Request::input('amount') ?? $session->amount);
        $method = Request::input('method') ?? 'cash';

        $customerId = $session->customer_id;

        $paymentId = Payment::create([
            'session_id'  => (int) $session->id,
            'customer_id' => $customerId ? (int) $customerId : null,
            'amount'      => $amount,
            'method'      => $method,
            'status'      => 'paid',
            'paid_at'     => date('Y-m-d H:i:s'),
            'accepted_by' => current_user()?->id ?? null,
        ]);

        $session->update([
            'payment_status' => 'paid',
            'payment_method' => $method,
        ]);

        if ($customerId) {
            Database::execute(
                'UPDATE customers SET outstanding_balance = GREATEST(0, outstanding_balance - ?) WHERE id = ?',
                [$amount, $customerId]
            );
        }

        \App\Services\AuditService::log('payment_received', 'payment', $paymentId, null, [
            'session' => (int) $session->id,
            'amount'  => $amount,
            'method'  => $method,
        ]);

        Response::success(['payment_id' => $paymentId, 'amount' => $amount], 'Payment accepted');
    }
}