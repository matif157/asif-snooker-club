<?php
/** @var array $expenses, $byCategory */
/** @var float $total */
/** @var string $from, $to */
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Expenses</h1>
            <p class="text-sm text-slate-400 mt-1">Track and manage club expenses</p>
        </div>
        <?php if (user_can('expenses.manage')): ?>
        <button onclick="document.getElementById('addExpenseModal').classList.remove('hidden')"
                class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Expense
        </button>
        <?php endif; ?>
    </div>

    <!-- Date Range Filter -->
    <div class="card p-5">
        <form method="GET" action="<?= e(url('/expenses')) ?>" class="flex flex-col sm:flex-row items-end gap-4">
            <div class="flex-1 w-full sm:w-auto">
                <label for="from" class="block text-xs font-medium text-slate-400 mb-1.5">From</label>
                <input type="date" id="from" name="from" value="<?= e($from) ?>"
                       class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
            </div>
            <div class="flex-1 w-full sm:w-auto">
                <label for="to" class="block text-xs font-medium text-slate-400 mb-1.5">To</label>
                <input type="date" id="to" name="to" value="<?= e($to) ?>"
                       class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
            </div>
            <button type="submit" class="btn-primary whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </button>
            <a href="<?= e(url('/expenses/export?from=' . urlencode($from) . '&to=' . urlencode($to))) ?>" class="btn-secondary whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </form>
    </div>

    <!-- Total + Category Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Total Expenses -->
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Total Expenses</p>
                    <p class="text-xs text-slate-500 mt-0.5">Selected period</p>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-rose-400 tracking-tight">Rs <?= number_format($total) ?></p>
            <p class="text-xs text-slate-500 mt-3"><?= count($expenses) ?> expense(s) recorded</p>
        </div>

        <!-- Category Breakdown -->
        <div class="lg:col-span-2 card p-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">By Category</h3>
            <?php if (empty($byCategory)): ?>
                <p class="text-sm text-slate-500 py-4 text-center">No expenses in this period</p>
            <?php else: ?>
                <?php arsort($byCategory); ?>
                <div class="space-y-2.5">
                    <?php foreach ($byCategory as $category => $catTotal): ?>
                        <?php $pct = $total > 0 ? round(($catTotal / $total) * 100, 1) : 0; ?>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-300 w-28 truncate font-medium"><?= e(ucfirst($category)) ?></span>
                            <div class="flex-1 h-2 rounded-full bg-white/5 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500/60" style="width: <?= $pct ?>%"></div>
                            </div>
                            <span class="text-xs font-semibold text-white w-20 text-right">Rs <?= number_format($catTotal) ?></span>
                            <span class="text-[11px] text-slate-500 w-12 text-right"><?= $pct ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">Expense History</h3>
        <?php if (empty($expenses)): ?>
            <p class="text-sm text-slate-500 py-8 text-center">No expenses found for the selected period</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th class="text-right">Amount</th>
                            <th>Vendor</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <?php $canApprove = user_can('finance.view') || current_user()?->role === 'owner'; ?>
                            <tr>
                                <td class="text-slate-400"><?= e(date('M j, Y', strtotime($expense['expense_date']))) ?></td>
                                <td>
                                    <span class="badge badge-emerald"><?= e(ucfirst($expense['category'])) ?></span>
                                </td>
                                <td class="text-right font-semibold text-white">Rs <?= number_format((float) $expense['amount']) ?></td>
                                <td class="text-slate-400"><?= e($expense['vendor'] ?? '—') ?></td>
                                <td class="text-slate-400 max-w-[220px] truncate"><?= e($expense['description'] ?? '—') ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-<?= match($expense['status'] ?? 'approved') {
                                            'approved' => 'emerald',
                                            'pending'  => 'amber',
                                            'rejected' => 'rose',
                                            default     => 'slate',
                                        } ?>"><?= e(ucfirst($expense['status'] ?? 'approved')) ?></span>
                                        <?php if (($expense['status'] ?? '') !== 'approved' && $canApprove): ?>
                                            <form method="POST" action="<?= e(url('/expenses/' . (int) $expense['id'] . '/status')) ?>" class="inline-flex gap-1">
                                                <?= csrf_field() ?>
                                                <?php if (($expense['status'] ?? '') !== 'approved'): ?>
                                                    <input type="hidden" name="status" value="approved">
                                                    <button class="text-xs text-emerald-400 hover:text-emerald-300 font-medium" title="Approve">Approve</button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (($expense['status'] ?? '') !== 'rejected' && $canApprove): ?>
                                            <form method="POST" action="<?= e(url('/expenses/' . (int) $expense['id'] . '/status')) ?>" class="inline-flex gap-1">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="text-xs text-rose-400 hover:text-rose-300 font-medium" title="Reject">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpenseModal" class="hidden">
    <div class="modal-overlay" onclick="if(event.target===this) document.getElementById('addExpenseModal').classList.add('hidden')">
        <div class="modal-card">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-white">Add Expense</h2>
                    <button onclick="document.getElementById('addExpenseModal').classList.add('hidden')"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Form -->
                <form method="POST" action="<?= e(url('/expenses')) ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label for="category" class="block text-xs font-medium text-slate-400 mb-1.5">Category *</label>
                        <select id="category" name="category" required
                                class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                            <option value="">Select category</option>
                            <option value="electricity">Electricity</option>
                            <option value="labour">Labour</option>
                            <option value="rent">Rent</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="cleaning">Cleaning</option>
                            <option value="supplies">Supplies</option>
                            <option value="internet">Internet</option>
                            <option value="security">Security</option>
                            <option value="camera">Camera/CCTV</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="amount" class="block text-xs font-medium text-slate-400 mb-1.5">Amount (Rs) *</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required
                                   placeholder="0.00"
                                   class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                        </div>
                        <div>
                            <label for="expense_date" class="block text-xs font-medium text-slate-400 mb-1.5">Date *</label>
                            <input type="date" id="expense_date" name="expense_date" required
                                   value="<?= e(date('Y-m-d')) ?>"
                                   class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="paid_by" class="block text-xs font-medium text-slate-400 mb-1.5">Paid By</label>
                            <input type="text" id="paid_by" name="paid_by"
                                   placeholder="e.g. Owner, Manager"
                                   class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                        </div>
                        <div>
                            <label for="vendor" class="block text-xs font-medium text-slate-400 mb-1.5">Vendor</label>
                            <input type="text" id="vendor" name="vendor"
                                   placeholder="Vendor or supplier"
                                   class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-medium text-slate-400 mb-1.5">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  placeholder="Additional details about this expense"
                                  class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition resize-none"></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Save Expense
                        </button>
                        <button type="button"
                                onclick="document.getElementById('addExpenseModal').classList.add('hidden')"
                                class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
