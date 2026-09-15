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

class PaymentController extends Controller
{
    public function index(): void
    {
        $payments    = Payment::recent(50);
        $outstanding = Payment::outstandingCustomers(10);
        $todayRev    = Payment::todayRevenueByMethod();

        $this->view('payments/index', [
            'payments'    => $payments,
            'outstanding' => $outstanding,
            'todayRev'    => $todayRev,
        ]);
    }

    public function store(): void
    {
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
                $session->update([
                    'payment_status' => 'paid',
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

        if (Request::isAjax()) {
            Response::success(['id' => $paymentId], 'Payment recorded');
        }
        Response::redirect('/payments');
    }

    public function apiPay(int $id): void
    {
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

        Response::success(['payment_id' => $paymentId, 'amount' => $amount], 'Payment accepted');
    }
}