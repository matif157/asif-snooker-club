<?php
/** @var array $sessions, $tables */
/** @var string $from, $to */
/** @var int $tableId */
/** @var string $status */

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
        <a href="<?= e(url('/tables?start_session=1')) ?>" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Session
        </a>
    </div>

    <!-- Filters -->
    <div class="card p-4 sm:p-5">
        <form method="GET" action="<?= e(url('/sessions')) ?>" class="flex flex-col sm:flex-row flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">From</label>
                <input type="date" name="from" value="<?= e($from) ?>" class="input !w-auto">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">To</label>
                <input type="date" name="to" value="<?= e($to) ?>" class="input !w-auto">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Table</label>
                <select name="table_id" class="input !w-auto">
                    <option value="0">All tables</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= $tableId === (int) $t['id'] ? 'selected' : '' ?>>
                            #<?= e($t['number']) ?> — <?= e($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Payment</label>
                <select name="status" class="input !w-auto">
                    <option value="">All</option>
                    <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
                    <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                </select>
            </div>
            <button type="submit" class="btn-secondary">Filter</button>
            <a href="<?= e(url('/sessions')) ?>" class="text-xs text-slate-500 hover:text-emerald-400 self-center">Reset</a>
        </form>
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