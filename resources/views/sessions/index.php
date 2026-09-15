<?php
/** @var array $sessions, $stats */
use App\Models\ClubSession;
use App\Models\Payment;

$sessions = \App\Core\Database::query(
    "SELECT s.*,
            t.number AS table_number,
            t.name AS table_name,
            c.name AS customer_name,
            u.name AS staff_name
     FROM sessions s
     JOIN tables t ON t.id = s.table_id
     LEFT JOIN customers c ON c.id = s.customer_id
     LEFT JOIN users u ON u.id = s.staff_id
     ORDER BY s.id DESC LIMIT 100"
);

$totalRevenue = array_sum(array_map(fn($s) => (float) $s['amount'], $sessions));
$paidCount   = count(array_filter($sessions, fn($s) => $s['payment_status'] === 'paid'));
$unpaidTotal = array_sum(array_map(fn($s) => $s['payment_status'] === 'unpaid' ? (float) $s['amount'] : 0, $sessions));
?>

<div class="space-y-6 fade-in">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Sessions</h1>
            <p class="text-sm text-slate-400 mt-1">All table sessions &amp; billing history</p>
        </div>
        <a href="/tables?start_session=1" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Session
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Total Sessions</p>
            <p class="text-2xl font-bold text-white mt-2"><?= count($sessions) ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Billed Revenue</p>
            <p class="text-2xl font-bold text-emerald-400 mt-2">Rs <?= number_format($totalRevenue) ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Paid Sessions</p>
            <p class="text-2xl font-bold text-white mt-2"><?= $paidCount ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Unpaid</p>
            <p class="text-2xl font-bold text-rose-400 mt-2">Rs <?= number_format($unpaidTotal) ?></p>
        </div>
    </div>

    <!-- Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Customer</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Duration</th>
                        <th class="text-right">Amount</th>
                        <th>Payment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sessions)): ?>
                        <tr><td colspan="8" class="text-center py-10 text-slate-500">No sessions yet — start one from the Tables screen</td></tr>
                    <?php else: ?>
                        <?php foreach ($sessions as $s): ?>
                            <?php
                                $duration = $s['end_time']
                                    ? strtotime($s['end_time']) - strtotime($s['start_time']) - (int) ($s['paused_total_sec'] ?? 0)
                                    : time() - strtotime($s['start_time']) - (int) ($s['paused_total_sec'] ?? 0);
                                $statusColor = match($s['payment_status']) {
                                    'paid'   => 'emerald',
                                    'partial'=> 'amber',
                                    'unpaid' => 'rose',
                                    default  => 'slate',
                                };
                            ?>
                            <tr>
                                <td class="font-medium text-white">#<?= e($s['table_number']) ?></td>
                                <td><?= e($s['customer_name'] ?? 'Walk-in') ?></td>
                                <td class="text-slate-400"><?= date('M j, g:i A', strtotime($s['start_time'])) ?></td>
                                <td class="text-slate-400"><?= $s['end_time'] ? date('M j, g:i A', strtotime($s['end_time'])) : '—' ?></td>
                                <td class="font-mono text-xs <?= $s['status'] === 'active' ? 'text-emerald-400 font-semibold' : 'text-slate-400' ?>">
                                    <?= format_duration(max(0, $duration)) ?>
                                </td>
                                <td class="text-right font-medium text-white">Rs <?= number_format((float) $s['amount']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $statusColor ?>">
                                        <?= ucfirst($s['payment_status']) ?>
                                        <?php if ($s['payment_method']): ?> · <?= ucfirst($s['payment_method']) ?><?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-slate <?= $s['status'] === 'active' ? 'badge-emerald' : '' ?>">
                                        <?= ucfirst($s['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>