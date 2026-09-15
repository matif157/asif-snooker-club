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
        $data = Request::all();
        $errors = $this->validate($data, [
            'amount'       => 'required|numeric',
            'category'     => 'required',
            'expense_date' => 'required',
        ]);

        if (!empty($errors)) {
            Response::redirect('/expenses');
        }

        Expense::create([
            'category'      => $data['category'],
            'amount'        => (float) $data['amount'],
            'expense_date'  => $data['expense_date'],
            'paid_by'       => $data['paid_by'] ?? null,
            'vendor'        => $data['vendor'] ?? null,
            'description'   => $data['description'] ?? null,
            'status'        => 'approved',
            'approved_by'   => current_user()?->id ?? null,
            'created_by'    => current_user()?->id ?? null,
        ]);

        if (Request::isAjax()) {
            Response::success([], 'Expense recorded');
        }
        Response::redirect('/expenses');
    }
}