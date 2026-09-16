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
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <a href="#" onclick="return sharePnl(this)"
           data-month="<?= e($month) ?>"
           data-revenue="<?= (int) $revenue ?>" data-expenses="<?= (int) $expenses ?>"
           data-net="<?= (int) $net ?>" data-txns="<?= (int) $txns ?>" data-sessions="<?= (int) $sessionCount ?>"
           class="btn-primary self-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.94 6.45 17.5 2 12.04 2zm5.83 14.13c-.25.7-1.45 1.33-2.02 1.42-.52.08-1.17.11-1.88-.12-.43-.14-.99-.32-1.7-.63-3-1.3-4.95-4.32-5.1-4.52-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.17 1.04-2.47.27-.3.59-.37.79-.37h.57c.18 0 .43-.07.67.51.25.6.85 2.07.92 2.22.08.15.13.33.03.53-.1.2-.15.32-.3.5-.15.18-.32.4-.45.53-.15.15-.31.31-.13.61.18.3.79 1.3 1.7 2.11 1.17 1.04 2.15 1.37 2.46 1.52.3.15.48.13.66-.08.18-.2.76-.88.96-1.19.2-.3.4-.25.67-.15.28.1 1.75.83 2.05.98.3.15.5.22.57.35.08.13.08.73-.17 1.42z"/></svg>
            Share Month to WhatsApp
        </a>
    </div>

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
function sharePnl(el) {
    const text = 'Monthly closing ' + el.dataset.month +
        ' — Revenue: Rs ' + Number(el.dataset.revenue).toLocaleString() +
        ', Expenses: Rs ' + Number(el.dataset.expenses).toLocaleString() +
        ', Net: Rs ' + Number(el.dataset.net).toLocaleString() +
        ' (' + el.dataset.txns + ' payments, ' + el.dataset.sessions + ' sessions) — Asif Snooker Club';
    window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
    return false;
}

const pnlCtx = document.getElementById('pnlChart');
if (pnlCtx) {
    const _aHex = getComputedStyle(document.documentElement).getPropertyValue('--a-500').trim() || '#10b981';
    const days = <?= json_encode($days) ?>;
    new Chart(pnlCtx, {
        type: 'bar',
        data: {
            labels: days.map(d => d.date.slice(8) + '/', ),
            datasets: [
                { label: 'Revenue', data: days.map(d => d.revenue), backgroundColor: _aHex + '59', borderColor: _aHex, borderWidth: 1, borderRadius: 4 },
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