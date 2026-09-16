<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index(): void
    {
        if (!user_can('expenses.view')) {
            $this->error('You do not have permission to view expenses.', 403);
        }

        $from = Request::get('from', date('Y-m-01'));
        $to   = Request::get('to', date('Y-m-d'));

        $expenses = Expense::forRange($from, $to);
        $total = array_sum(array_column($expenses, 'amount'));

        $byCategory = [];
        foreach ($expenses as $exp) {
            $cat = $exp['category'];
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + (float) $exp['amount'];
        }

        $this->view('expenses/index', [
            'expenses'    => $expenses,
            'total'       => $total,
            'byCategory'  => $byCategory,
            'from'        => $from,
            'to'          => $to,
        ]);
    }

    public function store(): void
    {
        if (!user_can('expenses.manage')) {
            $this->error('You do not have permission to manage expenses.', 403);
        }

        $data = Request::all();
        $errors = $this->validate($data, [
            'amount'       => 'required|numeric',
            'category'     => 'required',
            'expense_date' => 'required',
        ]);

        if (!empty($errors)) {
            Response::redirect('/expenses');
        }

        // Owner / admin / finance role records expenses as immediately approved
        $canApprove = user_can('finance.view') || current_user()?->role === 'owner';
        $status = $canApprove ? 'approved' : 'pending';

        Expense::create([
            'category'      => $data['category'],
            'amount'        => (float) $data['amount'],
            'expense_date'  => $data['expense_date'],
            'paid_by'       => $data['paid_by'] ?? null,
            'vendor'        => $data['vendor'] ?? null,
            'description'   => $data['description'] ?? null,
            'status'        => $status,
            'approved_by'   => $canApprove ? (current_user()?->id ?? null) : null,
            'created_by'    => current_user()?->id ?? null,
        ]);

        if (Request::isAjax()) {
            Response::success(['status' => $status], 'Expense recorded');
        }
        Response::redirect('/expenses');
    }

    public function setStatus(int $id): void
    {
        $expense = Expense::find($id);
        if (!$expense) {
            Response::error('Expense not found', 404);
        }

        if (!user_can('finance.view') && current_user()?->role !== 'owner') {
            $this->error('Only the owner or a finance admin can approve expenses.');
        }

        $status = Request::input('status');
        if (!in_array($status, ['approved', 'rejected'])) {
            Response::error('Invalid status');
        }

        $expense->update([
            'status'      => $status,
            'approved_by' => current_user()?->id ?? null,
        ]);

        \App\Services\AuditService::log('expense_' . $status, 'expense', $expense->id, null, [
            'amount'   => (float) $expense->amount,
            'category' => $expense->category,
        ]);

        if (Request::isAjax()) {
            Response::success(['status' => $status], 'Expense updated');
        }
        Response::redirect('/expenses');
    }
}