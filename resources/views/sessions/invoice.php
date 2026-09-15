<?php
/** @var array $s, $settings */
/** @var string $operator */
$clubName    = $settings['club_name'] ?? 'Asif Snooker Club';
$clubPhone   = $settings['club_phone'] ?? '+92 300 1234567';
$clubAddress = $settings['club_address'] ?? '';
$currency    = $settings['currency'] ?? 'Rs';

$rateTypes = \App\Models\ClubSession::RATE_TYPES;
$paidTotal = (float) ($s['paid_total'] ?? 0);
$amount    = (float) ($s['amount'] ?? 0);
$discount  = (float) ($s['discount'] ?? 0);
$extra     = (float) ($s['extra_charges'] ?? 0);
$due       = max(0, $amount - $paidTotal);
$duration  = max(0, (strtotime($s['end_time'] ?? 'now') - strtotime($s['start_time'])) - (int) ($s['paused_total_sec'] ?? 0));
?>
<div class="receipt-sheet bg-white rounded-xl shadow-sm border border-slate-200 p-7 sm:p-8">

    <!-- Header -->
    <div class="flex items-start justify-between border-b-2 border-dashed border-slate-200 pb-5 mb-5">
        <div>
            <h1 class="text-lg font-black text-slate-900 tracking-tight uppercase"><?= e($clubName) ?></h1>
            <?php if ($clubAddress): ?><p class="text-xs text-slate-500 mt-1"><?= e($clubAddress) ?></p><?php endif; ?>
            <p class="text-xs text-slate-500">Tel: <?= e($clubPhone) ?></p>
        </div>
        <div class="text-right">
            <p class="text-[11px] uppercase tracking-widest text-slate-400 font-semibold">Invoice</p>
            <p class="text-base font-bold text-slate-900 mt-0.5">#IV-<?= str_pad((string) $s['id'], 5, '0', STR_PAD_LEFT) ?></p>
            <p class="text-xs text-slate-500"><?= e(date('M j, Y  g:i A', strtotime($s['end_time'] ?? $s['start_time']))) ?></p>
        </div>
    </div>

    <!-- Billed to -->
    <div class="mb-5">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 mb-1">Billed To</p>
        <p class="font-semibold text-slate-900"><?= e($s['customer_name'] ?? 'Walk-in Customer') ?></p>
        <?php if (!empty($s['customer_phone'])): ?>
            <p class="text-sm text-slate-500"><?= e($s['customer_phone']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Line items -->
    <table class="w-full text-sm mb-5">
        <thead>
            <tr class="text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                <th class="text-left py-2">Description</th>
                <th class="text-right py-2">Rate</th>
                <th class="text-right py-2">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-dashed border-slate-100">
                <td class="py-2.5 text-slate-900">
                    Snooker session — Table #<?= (int) $s['table_number'] ?> (<?= e($s['table_name'] ?? '') ?>)
                    <div class="text-xs text-slate-500 mt-0.5">
                        <?= date('g:i A', strtotime($s['start_time'])) ?> — <?= date('g:i A', strtotime($s['end_time'] ?? $s['start_time'])) ?>
                        · <?= gmdate('H:i:s', $duration) ?> billed
                        · <?= e($rateTypes[$s['rate_type']] ?? $s['rate_type']) ?>
                    </div>
                </td>
                <td class="text-right whitespace-nowrap text-slate-700"><?= $currency ?> <?= number_format((float) $s['rate']) ?>/hr</td>
                <td class="text-right font-semibold text-slate-900"><?= $currency ?> <?= number_format($amount) ?></td>
            </tr>
            <?php if ($extra > 0): ?>
            <tr class="border-b border-dashed border-slate-100">
                <td class="py-2 text-slate-700">Extra charges</td>
                <td></td>
                <td class="text-right text-slate-900">+ <?= $currency ?> <?= number_format($extra) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($discount > 0): ?>
            <tr class="border-b border-dashed border-slate-100">
                <td class="py-2 text-slate-700">Discount</td>
                <td></td>
                <td class="text-right text-rose-600">- <?= $currency ?> <?= number_format($discount) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="bg-slate-50 rounded-xl px-4 py-4 mb-5 space-y-1.5">
        <div class="flex justify-between text-sm text-slate-600"><span>Subtotal</span><span><?= $currency ?> <?= number_format($amount) ?></span></div>
        <div class="flex justify-between text-sm text-slate-600"><span>Paid</span><span class="text-emerald-600 font-medium">- <?= $currency ?> <?= number_format($paidTotal) ?></span></div>
        <div class="flex justify-between items-center pt-2 border-t border-dashed border-slate-300">
            <span class="font-semibold text-slate-900"><?= $due > 0 ? 'Balance Due' : 'Paid in Full' ?></span>
            <span class="text-xl font-black <?= $due > 0 ? 'text-rose-600' : 'text-emerald-600' ?>"><?= $currency ?> <?= number_format($due) ?></span>
        </div>
        <div class="flex justify-between text-xs text-slate-500">
            <span>Status</span>
            <span class="uppercase font-semibold"><?= e($s['payment_status'] ?? 'unpaid') ?></span>
        </div>
    </div>

    <!-- Footer -->
    <div class="pt-4 border-t-2 border-dashed border-slate-200 flex justify-between text-xs text-slate-500">
        <span>Served by: <span class="text-slate-700 font-medium"><?= e($operator) ?></span></span>
        <span>Signature: ____________</span>
    </div>
    <p class="text-[11px] text-slate-400 mt-5 text-center">Thank you for playing at <?= e($clubName) ?> — this invoice is also your bill for settlement.</p>
</div>