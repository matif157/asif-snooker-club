<?php
/** @var array $payments, $outstanding, $todayRev */
$totalToday = array_sum(array_column($todayRev, 'total'));
?>

<div class="space-y-6 fade-in" x-data="{ showPaymentModal: false }">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Payments</h1>
            <p class="text-sm text-slate-400 mt-1">Revenue, transactions, and outstanding balances</p>
        </div>
        <button @click="showPaymentModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Record Payment
        </button>
    </div>

    <!-- Revenue by Method -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium">Today's Revenue</p>
                </div>
            </div>
            <p class="text-2xl sm:text-3xl font-bold text-white tracking-tight">Rs <?= number_format((float) $totalToday) ?></p>
        </div>

        <?php
        $methodIcons = [
            'cash'           => ['color' => 'emerald', 'label' => 'Cash'],
            'jazzcash'       => ['color' => 'sky',     'label' => 'JazzCash'],
            'bank_transfer'  => ['color' => 'violet',  'label' => 'Bank'],
            'card'           => ['color' => 'amber',   'label' => 'Card'],
            'other'          => ['color' => 'slate',   'label' => 'Other'],
        ];
        $methodsPresent = array_column($todayRev, 'method');
        $showMethods = array_slice($methodsPresent, 0, 3);
        if (count($showMethods) < 3) {
            foreach (['cash', 'jazzcash', 'bank_transfer'] as $m) {
                if (!in_array($m, $showMethods) && count($showMethods) < 3) {
                    $showMethods[] = $m;
                }
            }
        }
        foreach ($showMethods as $method):
            $info = $methodIcons[$method] ?? ['color' => 'slate', 'label' => ucfirst($method)];
            $methodData = null;
            foreach ($todayRev as $td) {
                if ($td['method'] === $method) { $methodData = $td; break; }
            }
        ?>
        <div class="stat-card">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-<?= $info['color'] ?>-500/15 flex items-center justify-center">
                    <span class="text-<?= $info['color'] ?>-400 text-xs font-bold"><?= strtoupper(mb_substr($info['label'], 0, 2)) ?></span>
                </div>
                <div class="text-right flex-1">
                    <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium"><?= e($info['label']) ?></p>
                    <p class="text-xs text-slate-500 mt-0.5"><?= (int) ($methodData['count'] ?? 0) ?> payments</p>
                </div>
            </div>
            <p class="text-xl font-bold text-white">Rs <?= number_format((float) ($methodData['total'] ?? 0)) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Payments -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Recent Payments</h3>
            <span class="badge badge-slate"><?= count($payments) ?> total</span>
        </div>
        <?php if (empty($payments)): ?>
            <div class="text-center py-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                <p class="text-sm text-slate-400">No payments recorded yet</p>
                <button @click="showPaymentModal = true" class="btn-primary mt-4">Record First Payment</button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Table</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="text-slate-400"><?= date('M j, g:i A', strtotime($p['paid_at'] ?? '')) ?></td>
                                <td class="font-medium text-white"><?= e($p['customer_name'] ?? 'Walk-in') ?></td>
                                <td class="text-slate-400"><?= !empty($p['table_number']) ? '#' . e($p['table_number']) : '—' ?></td>
                                <td>
                                    <span class="badge badge-sky"><?= e(\App\Models\Payment::METHODS[$p['method']] ?? $p['method'] ?? '') ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= match($p['status'] ?? '') {
                                        'paid'      => 'emerald',
                                        'pending'   => 'amber',
                                        'failed'    => 'rose',
                                        'refunded'  => 'violet',
                                        default     => 'slate',
                                    } ?>"><?= e(\App\Models\Payment::STATUSES[$p['status']] ?? $p['status'] ?? '') ?></span>
                                </td>
                                <td class="text-right font-medium text-emerald-400">Rs <?= number_format((float) ($p['amount'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Outstanding Balances -->
    <?php if (!empty($outstanding)): ?>
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Outstanding Balances</h3>
            <span class="badge badge-rose"><?= count($outstanding) ?> customers</span>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Last Visit</th>
                        <th class="text-right">Outstanding</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($outstanding as $c): ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/customers/' . $c['id'])) ?>" class="font-medium text-white hover:text-emerald-400 transition">
                                    <?= e($c['name'] ?? '') ?>
                                </a>
                            </td>
                            <td class="text-slate-400">
                                <?php if (!empty($c['phone'])): ?>
                                    <a href="tel:<?= e(preg_replace('/\D+/', '', $c['phone'])) ?>" class="hover:text-emerald-400 transition">
                                        <?= e($c['phone']) ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-400">
                                <?= !empty($c['last_visit_at']) ? date('M j', strtotime($c['last_visit_at'])) : '—' ?>
                            </td>
                            <td class="text-right font-bold text-rose-400">Rs <?= number_format((float) ($c['outstanding_balance'] ?? 0)) ?></td>
                            <td class="text-right">
                                <button @click="
                                    showPaymentModal = true;
                                    $dispatch('prefill-customer', {
                                        customer_id: <?= (int) $c['id'] ?>,
                                        customer_name: '<?= e($c['name'] ?? '') ?>',
                                        amount: <?= (float) ($c['outstanding_balance'] ?? 0) ?>
                                    })
                                " class="px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition">
                                    Pay Now
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Record Payment Modal -->
    <div x-show="showPaymentModal" x-cloak
         @prefill-customer.window="
            $refs.payCustomerId.value = $event.detail.customer_id;
            $refs.payAmount.value = $event.detail.amount;
         "
         class="modal-overlay" x-transition.opacity
         @keydown.escape.window="showPaymentModal = false">
        <div class="modal-card" @click.stop x-transition.scale.95>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-white">Record Payment</h2>
                    <button @click="showPaymentModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="<?= e(url('/payments')) ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <input type="hidden" name="customer_id" x-ref="payCustomerId" value="">

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount (Rs) *</label>
                        <input type="number" name="amount" step="0.01" min="0" required x-ref="payAmount"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="0">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Payment Method *</label>
                        <select name="method" required
                                class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                            <?php foreach (\App\Models\Payment::METHODS as $key => $label): ?>
                                <option value="<?= $key ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Session ID (optional)</label>
                        <input type="number" name="session_id"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="Link to a session">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Transaction Reference</label>
                        <input type="text" name="transaction_ref"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="Transaction ID or receipt #">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none resize-none"
                                  placeholder="Payment notes..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Record Payment
                        </button>
                        <button type="button" @click="showPaymentModal = false" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
