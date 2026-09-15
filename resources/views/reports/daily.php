<?php
/** @var string $date */
/** @var array $methods, $sessionStats */
/** @var float $collected, $expenseTotal, $outstanding */
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Daily Closing</h1>
            <p class="text-sm text-slate-400 mt-1">End-of-shift count for the manager.</p>
        </div>
        <form method="GET" action="<?= e(url('/reports/daily')) ?>" class="flex items-center gap-2">
            <input type="date" name="date" value="<?= e($date) ?>" class="input !w-auto">
            <button type="submit" class="btn-secondary">View</button>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Collected</span>
            <span class="text-2xl font-bold text-emerald-400">Rs <?= number_format($collected) ?></span>
            <span class="text-xs text-slate-500">across <?= count($methods) ?> method(s)</span>
        </div>
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Sessions</span>
            <span class="text-2xl font-bold text-white"><?= number_format($sessionStats['count'] ?? 0) ?></span>
            <span class="text-xs text-slate-500">billed Rs <?= number_format($sessionStats['billed'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Expenses</span>
            <span class="text-2xl font-bold text-rose-400">- Rs <?= number_format($expenseTotal) ?></span>
            <span class="text-xs text-slate-500">for <?= e($date) ?></span>
        </div>
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Outstanding</span>
            <span class="text-2xl font-bold <?= $outstanding > 0 ? 'text-amber-400' : 'text-emerald-400' ?>">Rs <?= number_format($outstanding) ?></span>
            <span class="text-xs text-slate-500">unpaid sessions</span>
        </div>
    </div>

    <!-- Net -->
    <div class="card p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-sm font-medium text-slate-400">Net position for <?= e($date) ?></h3>
            <p class="text-2xl font-bold text-white mt-1">Rs <?= number_format($collected - $expenseTotal) ?></p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="#" onclick="window.print(); return false;" class="btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2z"/></svg>
                Print
            </a>
            <a href="#" onclick="return shareClosing(this)" data-date="<?= e($date) ?>"
               data-collected="<?= (int) $collected ?>" data-expenses="<?= (int) $expenseTotal ?>"
               data-net="<?= (int) ($collected - $expenseTotal) ?>" class="btn-primary">
                Share to WhatsApp
            </a>
        </div>
    </div>

    <!-- Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Payments by Method</h3>
            <?php if (empty($methods)): ?>
                <p class="text-sm text-slate-500">No payments on this date.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($methods as $method => $total): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-400 capitalize"><?= e(str_replace('_', ' ', $method)) ?></span>
                            <span class="font-semibold text-white">Rs <?= number_format($total) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Club Activity</h3>
            <div class="space-y-2.5 max-h-72 overflow-y-auto">
                <?php $act = $activity ?? []; if (empty($act)): ?>
                    <p class="text-sm text-slate-500">No recent activity.</p>
                <?php else: foreach ($act as $entry): ?>
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="badge badge-violet shrink-0"><?= e($entry['action']) ?></span>
                            <span class="text-slate-400 truncate"><?= e($entry['user_name'] ?? 'system') ?></span>
                        </div>
                        <span class="text-slate-600 shrink-0"><?= date('H:i', strtotime($entry['created_at'])) ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
function shareClosing(el) {
    const date = el.dataset.date;
    const collected = el.dataset.collected;
    const expenses = el.dataset.expenses;
    const net = el.dataset.net;
    const text = 'Daily closing ' + date + ' — Collected: Rs ' + Number(collected).toLocaleString() +
                 ', Expenses: Rs ' + Number(expenses).toLocaleString() +
                 ', Net: Rs ' + Number(net).toLocaleString() + ' — Asif Snooker Club';
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
    return false;
}
</script>