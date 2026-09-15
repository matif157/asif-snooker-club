<?php
/** @var array $customer, $sessions, $payments, $bookings */
use App\Services\SettingsService;
$phoneDigits = preg_replace('/\D+/', '', $customer['phone'] ?? '');
$waDigits    = preg_replace('/\D+/', '', $customer['whatsapp'] ?? $customer['phone'] ?? '');
$waTemplate  = str_replace('{name}', $customer['name'] ?? 'Guest', SettingsService::whatsappTemplate());
$catBadge    = match($customer['category'] ?? 'regular') {
    'vip'        => 'badge-emerald',
    'member'     => 'badge-sky',
    'tournament' => 'badge-violet',
    default      => 'badge-slate',
};
$catLabel = match($customer['category'] ?? 'regular') {
    'vip'        => 'VIP',
    'member'     => 'Member',
    'tournament' => 'Tournament',
    default      => 'Regular',
};
?>

<div class="space-y-6 fade-in" x-data="{ activeTab: 'sessions' }">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= e(url('/customers')) ?>" class="hover:text-emerald-400 transition">Customers</a>
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-slate-400"><?= e($customer['name'] ?? '') ?></span>
    </div>

    <!-- Profile Header -->
    <div class="card p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5">
            <!-- Avatar -->
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center flex-shrink-0 shadow-lg shadow-emerald-500/20">
                <span class="text-white text-2xl font-bold"><?= strtoupper(mb_substr(e($customer['name'] ?? '?'), 0, 1)) ?></span>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl font-bold text-white tracking-tight"><?= e($customer['name'] ?? '') ?></h1>
                    <span class="badge <?= $catBadge ?>"><?= $catLabel ?></span>
                </div>
                <div class="flex items-center gap-4 mt-2 flex-wrap text-sm text-slate-400">
                    <?php if (!empty($customer['phone'])): ?>
                        <a href="tel:+<?= e($phoneDigits) ?>" class="inline-flex items-center gap-1.5 hover:text-emerald-400 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <?= e($customer['phone'] ?? '') ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($waDigits)): ?>
                        <a href="https://wa.me/<?= e($waDigits) ?>?text=<?= rawurlencode($waTemplate) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-emerald-400 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                        <a href="https://wa.me/<?= e($waDigits) ?>?text=<?= rawurlencode($waTemplate) ?>" target="_blank" rel="noopener" class="btn-primary !py-1.5 !px-3 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12c0 1.95.53 3.77 1.46 5.33L2 22l4.79-1.45A9.94 9.94 0 0012 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm4.9 13.47c-.21.59-1.21 1.13-1.68 1.17-.45.04-.99.21-3.35-.7-2.83-1.09-4.62-3.95-4.76-4.13-.14-.18-1.13-1.51-1.13-2.88 0-1.37.72-2.04.97-2.32.26-.28.56-.35.75-.35.19 0 .38 0 .54.01.17.01.41-.07.64.49.25.59.79 2.02.86 2.17.07.14.11.31.02.51-.09.18-.14.29-.28.45l-.43.5c-.14.14-.29.3-.13.58.16.28.72 1.18 1.55 1.91 1.06.94 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.17-.19.7-.82.88-1.1.18-.28.37-.23.62-.14.26.09 1.65.78 1.93.92.28.14.47.21.54.33.07.12.07.68-.14 1.34z"/></svg>
                            Message via CRM
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($customer['email'])): ?>
                        <span class="inline-flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <?= e($customer['email'] ?? '') ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?= e(url('/customers/' . $customer['id'] . '/edit')) ?>" class="btn-secondary self-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Total Visits</p>
            <p class="text-2xl font-bold text-white"><?= (int) ($customer['total_visits'] ?? 0) ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Hours Played</p>
            <p class="text-2xl font-bold text-white"><?= number_format((float) ($customer['total_hours'] ?? 0), 1) ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Total Spent</p>
            <p class="text-2xl font-bold text-emerald-400">Rs <?= number_format((float) ($customer['total_spent'] ?? 0)) ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Outstanding</p>
            <p class="text-2xl font-bold <?= (float) ($customer['outstanding_balance'] ?? 0) > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">
                Rs <?= number_format((float) ($customer['outstanding_balance'] ?? 0)) ?>
            </p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card">
        <div class="flex border-b border-white/[0.06]">
            <?php foreach (['sessions' => 'Sessions', 'payments' => 'Payments', 'bookings' => 'Bookings'] as $tab => $label): ?>
                <button @click="activeTab = '<?= $tab ?>'"
                        class="px-5 py-3 text-sm font-medium transition relative
                               <?= 'text-slate-400 hover:text-white' ?>"
                        :class="activeTab === '<?= $tab ?>' ? 'text-emerald-400' : 'text-slate-400 hover:text-white'">
                    <?= $label ?>
                    <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-400 scale-x-0 transition-transform origin-left"
                          :class="activeTab === '<?= $tab ?>' ? 'scale-x-100' : ''"></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Sessions Tab -->
        <div x-show="activeTab === 'sessions'" x-cloak class="p-5">
            <?php if (empty($sessions)): ?>
                <p class="text-sm text-slate-500 text-center py-8">No sessions yet</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Table</th>
                                <th>Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th class="text-right">Amount</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s): ?>
                                <tr>
                                    <td class="font-medium text-white">#<?= e($s['table_number'] ?? '—') ?></td>
                                    <td class="text-slate-400"><?= date('M j, Y', strtotime($s['start_time'] ?? '')) ?></td>
                                    <td class="text-slate-400"><?= format_duration((int) ($s['duration_seconds'] ?? 0)) ?></td>
                                    <td>
                                        <span class="badge badge-<?= match($s['status'] ?? '') {
                                            'active'   => 'emerald',
                                            'completed'=> 'slate',
                                            'paused'   => 'amber',
                                            default    => 'rose',
                                        } ?>"><?= ucfirst(e($s['status'] ?? '')) ?></span>
                                    </td>
                                    <td class="text-right font-medium text-white">Rs <?= number_format((float) ($s['amount'] ?? 0)) ?></td>
                                    <td>
                                        <span class="badge badge-<?= match($s['payment_status'] ?? '') {
                                            'paid'    => 'emerald',
                                            'partial' => 'amber',
                                            'unpaid'  => 'rose',
                                            default   => 'slate',
                                        } ?>"><?= ucfirst(e($s['payment_status'] ?? '')) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payments Tab -->
        <div x-show="activeTab === 'payments'" x-cloak class="p-5">
            <?php if (empty($payments)): ?>
                <p class="text-sm text-slate-500 text-center py-8">No payments recorded</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Reference</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td class="text-slate-400"><?= date('M j, g:i A', strtotime($p['paid_at'] ?? '')) ?></td>
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
                                    <td class="text-slate-400 text-xs"><?= e($p['transaction_ref'] ?? '—') ?></td>
                                    <td class="text-right font-medium text-emerald-400">Rs <?= number_format((float) ($p['amount'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bookings Tab -->
        <div x-show="activeTab === 'bookings'" x-cloak class="p-5">
            <?php if (empty($bookings)): ?>
                <p class="text-sm text-slate-500 text-center py-8">No bookings yet</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Table</th>
                                <th>Time</th>
                                <th>Players</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td class="text-slate-400"><?= date('M j, Y', strtotime($b['booking_date'] ?? '')) ?></td>
                                    <td class="font-medium text-white">#<?= e($b['table_number'] ?? '—') ?></td>
                                    <td class="text-slate-400">
                                        <?= date('g:i A', strtotime($b['start_time'] ?? '')) ?> — <?= date('g:i A', strtotime($b['end_time'] ?? '')) ?>
                                    </td>
                                    <td class="text-slate-400"><?= (int) ($b['players_count'] ?? 0) ?></td>
                                    <td>
                                        <span class="badge badge-<?= e(\App\Models\Booking::STATUS_COLORS[$b['status']] ?? 'slate') ?>">
                                            <?= e(\App\Models\Booking::STATUS_LABELS[$b['status']] ?? $b['status'] ?? '') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
