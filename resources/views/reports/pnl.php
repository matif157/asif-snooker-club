<?php
/** @var string $month, $display, $prevMonth, $nextMonth */
/** @var float $revenue, $expenses, $net, $outstanding */
/** @var int $txns, $expCount, $sessionCount */
/** @var float $sessionBilled */
/** @var array $byMethod, $byCategory, $days */
?>
<div class="space-y-6 fade-in">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Profit &amp; Loss</h1>
            <p class="text-sm text-slate-400 mt-1">Monthly revenue, expenses and net position</p>
        </div>
        <form method="GET" action="<?= e(url('/reports/pnl')) ?>" class="flex items-center gap-2">
            <a href="<?= e(url('/reports/pnl?month=' . $prevMonth)) ?>" class="btn-secondary !px-3 !py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <input type="month" name="month" value="<?= e($month) ?>" class="input !w-auto">
            <a href="<?= e(url('/reports/pnl?month=' . $nextMonth)) ?>" class="btn-secondary !px-3 !py-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            <button type="submit" class="btn-secondary">Go</button>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Revenue</span>
            <span class="text-2xl font-bold text-emerald-400">Rs <?= number_format($revenue) ?></span>
            <span class="text-xs text-slate-500"><?= number_format($txns) ?> payments · <?= number_format($sessionCount) ?> sessions</span>
        </div>
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Expenses</span>
            <span class="text-2xl font-bold text-rose-400">- Rs <?= number_format($expenses) ?></span>
            <span class="text-xs text-slate-500"><?= number_format($expCount) ?> approved</span>
        </div>
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Net Position</span>
            <span class="text-2xl font-bold <?= $net >= 0 ? 'text-white' : 'text-rose-400' ?>">Rs <?= number_format($net) ?></span>
            <span class="text-xs text-slate-500"><?= $net >= 0 ? 'Profit' : 'Loss' ?></span>
        </div>
        <div class="stat-card">
            <span class="text-xs uppercase tracking-wider text-slate-500">Outstanding</span>
            <span class="text-2xl font-bold <?= $outstanding > 0 ? 'text-amber-400' : 'text-emerald-400' ?>">Rs <?= number_format($outstanding) ?></span>
            <span class="text-xs text-slate-500">unpaid sessions by <?= e($display) ?> end</span>
        </div>
    </div>

    <!-- Daily Chart -->
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-white mb-4">Daily Revenue vs Expenses — <?= e($display) ?></h3>
        <canvas id="pnlChart" height="90"></canvas>
    </div>

    <!-- Breakdowns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Revenue by Method</h3>
            <?php if (empty($byMethod)): ?>
                <p class="text-sm text-slate-500">No payments this month.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($byMethod as $m): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-400 capitalize"><?= e(str_replace('_', ' ', $m['method'])) ?></span>
                            <span class="font-semibold text-white">Rs <?= number_format((float) $m['total']) ?>
                                <span class="text-xs text-slate-500">(<?= (int) $m['count'] ?>)</span>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div class="pt-3 border-t border-white/[0.06] flex items-center justify-between text-sm">
                        <span class="text-slate-400">Total</span>
                        <span class="font-bold text-emerald-400">Rs <?= number_format($revenue) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Expenses by Category</h3>
            <?php if (empty($byCategory)): ?>
                <p class="text-sm text-slate-500">No approved expenses this month.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($byCategory as $c): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-400 capitalize"><?= e(str_replace('_', ' ', $c['category'])) ?></span>
                            <span class="font-medium text-white">Rs <?= number_format((float) $c['total']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="pt-3 border-t border-white/[0.06] flex items-center justify-between text-sm">
                        <span class="text-slate-400">Total</span>
                        <span class="font-bold text-rose-400">Rs <?= number_format($expenses) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            <div class="mt-5 pt-4 border-t border-dashed border-white/[0.08]">
                <p class="text-[11px] text-slate-500 leading-relaxed">Session billing this month: <span class="text-slate-300 font-medium">Rs <?= number_format($sessionBilled) ?></span> across <?= number_format($sessionCount) ?> completed sessions.</p>
            </div>
        </div>
    </div>

</div>

<script>
const pnlCtx = document.getElementById('pnlChart');
if (pnlCtx) {
    const days = <?= json_encode($days) ?>;
    new Chart(pnlCtx, {
        type: 'bar',
        data: {
            labels: days.map(d => d.date.slice(8) + '/', ),
            datasets: [
                { label: 'Revenue', data: days.map(d => d.revenue), backgroundColor: 'rgba(16,185,129,0.35)', borderColor: '#10b981', borderWidth: 1, borderRadius: 4 },
                { label: 'Expenses', data: days.map(d => d.expense), backgroundColor: 'rgba(244,63,94,0.35)', borderColor: '#f43f5e', borderWidth: 1, borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#94a3b8' } } },
            scales: {
                x: { ticks: { color: '#64748b', maxTicksLimit: 15 }, grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { ticks: { color: '#64748b' }, grid: { color: 'rgba(255,255,255,0.04)' } }
            }
        }
    });
}
</script>