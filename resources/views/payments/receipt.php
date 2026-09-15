<?php
/** @var array $p, $settings */
/** @var string $operator */
$clubName    = $settings['club_name'] ?? 'Asif Snooker Club';
$clubPhone   = $settings['club_phone'] ?? '+92 300 1234567';
$clubAddress = $settings['club_address'] ?? '';
$currency    = $settings['currency'] ?? 'Rs';

function amountInWords(float $num): string
{
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
             'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen',
             'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $scale = ['', 'Thousand', 'Million'];

    $whole = (int) floor($num);
    $words = '';

    $chunks = [];
    $n = $whole;
    if ($n === 0) { $words = 'Zero'; }
    while ($n > 0) {
        array_unshift($chunks, $n % 1000);
        $n = (int) ($n / 1000);
    }

    foreach ($chunks as $i => $chunk) {
        if ($chunk === 0) continue;
        $part = '';
        $hundreds = (int) ($chunk / 100);
        $rem = $chunk % 100;
        if ($hundreds > 0) { $part .= $ones[$hundreds] . ' Hundred '; }
        if ($rem > 0) {
            if ($rem < 20) { $part .= $ones[$rem] . ' '; }
            else { $part .= $tens[(int)($rem/10)] . ($rem % 10 ? ' ' . $ones[$rem % 10] : '') . ' '; }
        }
        $words .= $part . $scale[count($chunks) - 1 - $i] . ' ';
    }

    return trim($words) . ' Rupees Only';
}
?>
<div class="receipt-sheet bg-white rounded-xl shadow-sm border border-slate-200 p-7 sm:p-8">
    <!-- Header -->
    <div class="text-center border-b-2 border-dashed border-slate-200 pb-5 mb-5">
        <h1 class="text-lg font-black text-slate-900 tracking-tight uppercase"><?= e($clubName) ?></h1>
        <?php if ($clubAddress): ?><p class="text-xs text-slate-500 mt-1"><?= e($clubAddress) ?></p><?php endif; ?>
        <p class="text-xs text-slate-500">Tel: <?= e($clubPhone) ?></p>
        <p class="text-[11px] text-slate-400 mt-2 font-medium tracking-widest uppercase">Payment Receipt</p>
    </div>

    <!-- Meta -->
    <div class="flex justify-between text-sm mb-1">
        <span class="text-slate-500">Receipt No</span>
        <span class="font-semibold text-slate-900">#<?= str_pad((string) $p['id'], 6, '0', STR_PAD_LEFT) ?></span>
    </div>
    <div class="flex justify-between text-sm mb-1">
        <span class="text-slate-500">Date &amp; Time</span>
        <span class="text-slate-900"><?= e(date('M j, Y  g:i A', strtotime($p['paid_at']))) ?></span>
    </div>
    <div class="flex justify-between text-sm mb-1">
        <span class="text-slate-500">Payment Method</span>
        <span class="uppercase font-medium text-slate-900"><?= e(\App\Models\Payment::METHODS[$p['method']] ?? $p['method']) ?></span>
    </div>
    <?php if (!empty($p['transaction_ref'])): ?>
    <div class="flex justify-between text-sm mb-1">
        <span class="text-slate-500">Transaction Ref</span>
        <span class="font-mono text-slate-900"><?= e($p['transaction_ref']) ?></span>
    </div>
    <?php endif; ?>

    <!-- To -->
    <div class="mt-4 pt-4 border-t border-dashed border-slate-200">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 mb-1">Billed To</p>
        <p class="font-semibold text-slate-900"><?= e($p['customer_name'] ?? 'Walk-in Customer') ?></p>
        <?php if (!empty($p['customer_phone'])): ?>
            <p class="text-sm text-slate-500"><?= e($p['customer_phone']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Session details -->
    <?php if ($p['session_id']): ?>
    <div class="mt-4 pt-4 border-t border-dashed border-slate-200">
        <p class="text-[11px] uppercase tracking-wider text-slate-400 mb-2">Session Details</p>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-slate-500">Table</span>
            <span class="font-medium text-slate-900">#<?= (int) $p['table_number'] ?> — <?= e($p['table_name'] ?? '') ?></span>
        </div>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-slate-500">Started</span>
            <span class="text-slate-900"><?= e(date('M j, g:i A', strtotime($p['session_start']))) ?></span>
        </div>
        <?php if (!empty($p['session_end'])): ?>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-slate-500">Ended</span>
            <span class="text-slate-900"><?= e(date('M j, g:i A', strtotime($p['session_end']))) ?></span>
        </div>
        <div class="flex justify-between text-sm mb-1">
            <span class="text-slate-500">Session Total</span>
            <span class="text-slate-900"><?= $currency ?> <?= number_format((float) $p['session_amount']) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Amount -->
    <div class="mt-5 flex items-center justify-between bg-slate-50 rounded-xl px-4 py-4">
        <span class="text-sm font-semibold text-slate-700 uppercase tracking-wider">Amount Paid</span>
        <span class="text-2xl font-black text-emerald-600"><?= $currency ?> <?= number_format((float) $p['amount']) ?></span>
    </div>
    <p class="text-center text-[11px] text-slate-500 mt-3 italic"><?= e(amountInWords((float) $p['amount'])) ?></p>

    <!-- Footer -->
    <div class="mt-6 pt-4 border-t-2 border-dashed border-slate-200 text-center">
        <div class="flex justify-between text-xs text-slate-500">
            <span>Accepted by: <span class="text-slate-700 font-medium"><?= e($operator) ?></span></span>
            <span>Signature: ____________</span>
        </div>
        <p class="text-[11px] text-slate-400 mt-5">Thank you for playing at <?= e($clubName) ?>!<br>Please keep this receipt as proof of payment.</p>
    </div>
</div>