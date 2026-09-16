<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReminderService;

class ReminderController extends Controller
{
    public function index(): void
    {
        if (!user_can('reports.view') && !user_can('finance.view')) {
            $this->error('You do not have permission to view reminders.', 403);
        }

        $pending = ReminderService::pending();

        $this->view('reminders/index', [
            'bookings'    => $pending['bookings'],
            'outstanding' => $pending['outstanding'],
            'horizon'     => ReminderService::horizonMinutes(),
            'enabled'     => ReminderService::enabled(),
        ]);
    }
}