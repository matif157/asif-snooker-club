<?php
/** @var array $customers, $loans, $methods */
/** @var float $totalOutstanding, $openLoanAmount */
/** @var int $openLoanSessions */
?>

<div class="space-y-6 fade-in" x-data="{ showLoanModal: false, prefill: { customer_id: 0, name: '', amount: 0 } }">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Udhaar / Loans</h1>
            <p class="text-sm text-slate-400 mt-1">Customers who owe money and the sessions that created the balance</p>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-rose-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium flex-1 text-right">Total Outstanding</p>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-rose-400 tracking-tight">Rs <?= number_format($totalOutstanding) ?></p>
            <p class="text-xs text-slate-500 mt-1"><?= count($customers) ?> customer<?= count($customers) === 1 ? '' : 's' ?></p>
        </div>

        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium flex-1 text-right">Open Loan Sessions</p>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight"><?= (int) $openLoanSessions ?></p>
            <p class="text-xs text-slate-500 mt-1">Rs <?= number_format($openLoanAmount) ?> unpaid on tables</p>
        </div>

        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium flex-1 text-right">Settle Balance</p>
            </div>
            <p class="text-sm text-slate-400 mt-1">Record a payment against a customer to clear their udhaar.</p>
        </div>
    </div>

    <!-- Customers who owe -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Customers With Balance</h3>
            <span class="badge badge-rose"><?= count($customers) ?></span>
        </div>
        <?php if (empty($customers)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm text-slate-400">No outstanding loans. All balances are clear.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Loans</th>
                            <th>Last Visit</th>
                            <th class="text-right">Outstanding</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('/customers/' . (int) $c['id'])) ?>" class="font-medium text-white hover:text-emerald-400 transition">
                                        <?= e($c['name'] ?? '') ?>
                                    </a>
                                </td>
                                <td class="text-slate-400">
                                    <?php if (!empty($c['phone'])): ?>
                                        <a href="tel:<?= e(preg_replace('/\D+/', '', $c['phone'])) ?>" class="hover:text-emerald-400 transition"><?= e($c['phone']) ?></a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><span class="badge badge-amber"><?= (int) ($c['loan_count'] ?? 0) ?></span></td>
                                <td class="text-slate-400"><?= !empty($c['last_visit_at']) ? date('M j', strtotime($c['last_visit_at'])) : '—' ?></td>
                                <td class="text-right font-bold text-rose-400">Rs <?= number_format((float) ($c['outstanding_balance'] ?? 0)) ?></td>
                                <td class="text-right">
                                    <button type="button"
                                        @click="showLoanModal = true; prefill = { customer_id: <?= (int) $c['id'] ?>, name: <?= e(json_encode($c['name'] ?? '')) ?>, amount: <?= (float) ($c['outstanding_balance'] ?? 0) ?> }"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition">
                                        Receive Payment
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent loan sessions -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Loan History (Sessions)</h3>
            <span class="badge badge-slate"><?= count($loans) ?></span>
        </div>
        <?php if (empty($loans)): ?>
            <p class="text-sm text-slate-400 py-4">No loan sessions recorded yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Table</th>
                            <th>Customer</th>
                            <th>Players</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Loan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $l):
                            $players = trim(($l['player_winner'] ?? '') . ' vs ' . ($l['player_loser'] ?? ''), ' vs');
                        ?>
                            <tr>
                                <td class="text-slate-400"><?= !empty($l['ended_at']) ? date('M j, g:i A', strtotime($l['ended_at'])) : '—' ?></td>
                                <td class="text-slate-400">#<?= e($l['table_number'] ?? '') ?></td>
                                <td>
                                    <span class="font-medium text-white"><?= e($l['customer_name'] ?? $l['client_name'] ?? 'Walk-in') ?></span>
                                    <?php if (!empty($l['customer_phone'])): ?>
                                        <span class="block text-xs text-slate-500"><?= e($l['customer_phone']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-slate-400"><?= $players !== '' ? e($players) : '—' ?></td>
                                <td class="text-right text-slate-300">Rs <?= number_format((float) ($l['amount'] ?? 0)) ?></td>
                                <td class="text-right text-emerald-400">Rs <?= number_format((float) ($l['paid_total'] ?? 0)) ?></td>
                                <td class="text-right font-bold text-rose-400">Rs <?= number_format((float) ($l['loan_amount'] ?? 0)) ?></td>
                                <td>
                                    <span class="badge badge-<?= ($l['payment_status'] ?? '') === 'paid' ? 'emerald' : 'amber' ?>">
                                        <?= e(ucfirst($l['payment_status'] ?? 'unpaid')) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Receive Payment Modal -->
    <div x-show="showLoanModal" x-cloak class="modal-overlay" x-transition.opacity @keydown.escape.window="showLoanModal = false">
        <div class="modal-card" @click.stop x-transition.scale.95>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-white">Receive Payment</h2>
                    <button @click="showLoanModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <p class="text-sm text-slate-400 mb-4">
                    Clearing balance for <span class="font-semibold text-white" x-text="prefill.name"></span>
                    (<span x-text="'Rs ' + Number(prefill.amount).toLocaleString()"></span> owing)
                </p>
                <form method="POST" action="<?= e(url('/payments')) ?>" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="customer_id" :value="prefill.customer_id">
                    <input type="hidden" name="notes" value="Loan settlement">

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount Received (Rs) *</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required :value="prefill.amount"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Payment Method *</label>
                        <select name="method" required
                                class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                            <?php foreach ($methods as $key => $label): ?>
                                <option value="<?= e($key) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Save Payment
                        </button>
                        <button type="button" @click="showLoanModal = false" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
