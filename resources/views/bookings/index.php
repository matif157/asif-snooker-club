<?php
/** @var array $bookings, $tables, $upcoming, $bookingsPaid */
/** @var string $selectedDate */
?>

<div class="space-y-6 fade-in" x-data="{ showBookingModal: false }">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Bookings</h1>
            <p class="text-sm text-slate-400 mt-1">Manage table reservations for <span class="text-emerald-400 font-semibold"><?= date('l, M j, Y', strtotime($selectedDate)) ?></span></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(url('/bookings/calendar')) ?>" class="btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Calendar
            </a>
            <?php if (user_can('bookings.manage')): ?>
            <button @click="showBookingModal = true" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New Booking
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Date Selector -->
    <div class="card p-4 flex items-center gap-4 flex-wrap">
        <a href="<?= e(url('/bookings?date=' . date('Y-m-d', strtotime($selectedDate . ' -1 day')))) ?>"
           class="btn-secondary !px-3 !py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="relative flex-1 max-w-xs">
            <input type="date" value="<?= e($selectedDate) ?>"
                   onchange="window.location.href='<?= e(url('/bookings?date=')) ?>' + this.value"
                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
        </div>
        <a href="<?= e(url('/bookings?date=' . date('Y-m-d', strtotime($selectedDate . ' +1 day')))) ?>"
           class="btn-secondary !px-3 !py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="<?= e(url('/bookings?date=' . date('Y-m-d'))) ?>" class="btn-secondary text-xs">Today</a>
        <a href="<?= e(url('/bookings/export?date=' . $selectedDate)) ?>" class="btn-secondary text-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
    </div>

    <!-- Table Availability Grid -->
    <div>
        <h2 class="text-sm font-semibold text-white uppercase tracking-wider mb-3">Table Availability</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <?php foreach ($tables as $table): ?>
                <?php
                    $tableBookings = array_filter($bookings, fn($b) => (int)($b['table_id'] ?? 0) === (int)$table['id']);
                    $isBooked = count($tableBookings) > 0;
                    $statusClass = match($table['status'] ?? 'available') {
                        'occupied'    => 'table-tile--occupied',
                        'maintenance' => 'table-tile--maintenance',
                        'blocked'     => 'table-tile--blocked',
                        default       => $isBooked ? 'table-tile--reserved' : 'table-tile--available',
                    };
                ?>
                <div class="table-tile <?= $statusClass ?>">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-bold text-white">#<?= e($table['number']) ?></span>
                        <?php if ($isBooked): ?>
                            <span class="w-2 h-2 rounded-full bg-violet-400"></span>
                        <?php elseif ($table['status'] === 'available'): ?>
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <?php else: ?>
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-400 mb-2"><?= e($table['name'] ?? '') ?></p>
                    <?php if ($isBooked): ?>
                        <?php foreach ($tableBookings as $tb): ?>
                            <p class="text-[11px] text-violet-400 font-medium">
                                <?= date('g:i A', strtotime($tb['start_time'] ?? '')) ?> — <?= date('g:i A', strtotime($tb['end_time'] ?? '')) ?>
                            </p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-xs text-slate-500">Free all day</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bookings for Selected Date -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Bookings for <?= date('M j, Y', strtotime($selectedDate)) ?></h3>
            <span class="badge badge-slate"><?= count($bookings) ?> total</span>
        </div>
        <?php if (empty($bookings)): ?>
            <div class="text-center py-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-sm text-slate-400">No bookings for this date</p>
                <button @click="showBookingModal = true" class="btn-primary mt-4">Create First Booking</button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Table</th>
                            <th>Customer</th>
                            <th>Players</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td class="font-medium text-white">
                                    <?= date('g:i A', strtotime($b['start_time'] ?? '')) ?>
                                    <span class="text-slate-500"> — </span>
                                    <?= date('g:i A', strtotime($b['end_time'] ?? '')) ?>
                                </td>
                                <td>
                                    <span class="text-slate-300">#<?= e($b['table_number'] ?? '') ?></span>
                                    <span class="text-slate-500 text-xs block"><?= e($b['table_name'] ?? '') ?></span>
                                </td>
                                <td>
                                    <p class="text-white font-medium">
                                        <?= e($b['customer_linked_name'] ?? $b['customer_name'] ?? 'Walk-in') ?>
                                        <?php if (str_contains((string) ($b['notes'] ?? ''), 'member portal')): ?>
                                            <span class="badge badge-sky align-middle ml-1 !text-[10px] !px-1.5">Portal</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($b['customer_phone'])): ?>
                                        <a href="tel:<?= e(preg_replace('/\D+/', '', $b['customer_phone'])) ?>"
                                           class="text-xs text-slate-500 hover:text-emerald-400 transition">
                                            <?= e($b['customer_phone']) ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php $paid = (float) ($bookingsPaid[$b['id']] ?? 0); ?>
                                    <?php if ($paid > 0): ?>
                                        <p class="text-[11px] text-emerald-400 font-medium mt-0.5">Paid Rs <?= number_format($paid) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="text-slate-400"><?= (int) ($b['players_count'] ?? 0) ?></td>
                                <td>
                                    <span class="badge badge-<?= e(\App\Models\Booking::STATUS_COLORS[$b['status']] ?? 'slate') ?>">
                                        <?= e(\App\Models\Booking::STATUS_LABELS[$b['status']] ?? $b['status'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <?php if (user_can('payments.manage')): ?>
                                            <button type="button"
                                                    onclick="openPayModal(<?= (int) $b['id'] ?>, '<?= e(str_replace("'", '', $b['customer_linked_name'] ?? $b['customer_name'] ?? 'Walk-in')) ?>')"
                                                    title="Record payment for this booking"
                                                    class="p-2 rounded-lg text-slate-500 hover:text-emerald-400 hover:bg-white/5 transition">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        <?php endif; ?>
                                        <a href="#" onclick="return shareBooking(this)"
                                           data-phone="<?= e($b['customer_linked_phone'] ?? $b['customer_phone'] ?? '') ?>"
                                           data-name="<?= e($b['customer_linked_name'] ?? $b['customer_name'] ?? 'Walk-in') ?>"
                                           data-table="<?= e($b['table_number'] ?? '') ?>"
                                           data-date="<?= e($b['booking_date'] ?? '') ?>"
                                           data-start="<?= e(date('g:i A', strtotime($b['start_time'] ?? ''))) ?>"
                                           data-end="<?= e(date('g:i A', strtotime($b['end_time'] ?? ''))) ?>"
                                           data-players="<?= (int) ($b['players_count'] ?? 0) ?>"
                                           title="Send booking reminder via WhatsApp"
                                           class="p-2 rounded-lg text-slate-500 hover:text-emerald-400 hover:bg-white/5 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.94 6.45 17.5 2 12.04 2zm5.83 14.13c-.25.7-1.45 1.33-2.02 1.42-.52.08-1.17.11-1.88-.12-.43-.14-.99-.32-1.7-.63-3-1.3-4.95-4.32-5.1-4.52-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.17 1.04-2.47.27-.3.59-.37.79-.37h.57c.18 0 .43-.07.67.51.25.6.85 2.07.92 2.22.08.15.13.33.03.53-.1.2-.15.32-.3.5-.15.18-.32.4-.45.53-.15.15-.31.31-.13.61.18.3.79 1.3 1.7 2.11 1.17 1.04 2.15 1.37 2.46 1.52.3.15.48.13.66-.08.18-.2.76-.88.96-1.19.2-.3.4-.25.67-.15.28.1 1.75.83 2.05.98.3.15.5.22.57.35.08.13.08.73-.17 1.42z"/></svg>
                                        </a>
                                        <?php if (in_array($b['status'] ?? '', ['requested', 'confirmed'])): ?>
                                            <?php if (($b['status'] ?? '') === 'requested'): ?>
                                                <form method="POST" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>" class="inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-sky-500/10 text-sky-400 hover:bg-sky-500/20 transition">Confirm</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="arrived">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition">Arrived</button>
                                            </form>
                                            <form method="POST" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($b['status'] === 'arrived'): ?>
                                            <form method="POST" action="<?= e(url('/bookings/' . $b['id'] . '/status')) ?>" class="inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-medium bg-sky-500/10 text-sky-400 hover:bg-sky-500/20 transition">Start</button>
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

    <!-- Upcoming Bookings -->
    <?php if (!empty($upcoming)): ?>
    <div class="card p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Upcoming Bookings</h3>
        </div>
        <div class="space-y-2.5">
            <?php foreach ($upcoming as $u): ?>
                <div class="flex items-center justify-between py-2.5 px-4 rounded-xl bg-white/[0.02] border border-white/[0.04]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-500/10 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">
                                <?= e($u['customer_linked_name'] ?? $u['customer_name'] ?? 'Walk-in') ?>
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                <?= e(date('D, M j', strtotime($u['booking_date'] ?? ''))) ?> ·
                                Table #<?= e($u['table_number'] ?? '') ?> ·
                                <?= date('g:i A', strtotime($u['start_time'] ?? '')) ?> — <?= date('g:i A', strtotime($u['end_time'] ?? '')) ?>
                            </p>
                        </div>
                    </div>
                    <span class="badge badge-<?= e(\App\Models\Booking::STATUS_COLORS[$u['status']] ?? 'slate') ?>">
                        <?= e(\App\Models\Booking::STATUS_LABELS[$u['status']] ?? $u['status'] ?? '') ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add Booking Modal -->
    <div x-show="showBookingModal" x-cloak
         class="modal-overlay" x-transition.opacity
         @keydown.escape.window="showBookingModal = false">
        <div class="modal-card" @click.stop x-transition.scale.95>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-white">New Booking</h2>
                    <button @click="showBookingModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="<?= e(url('/bookings')) ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Table *</label>
                            <select name="table_id" required
                                    class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                                <option value="">Select table</option>
                                <?php foreach ($tables as $t): ?>
                                    <option value="<?= (int) $t['id'] ?>"><?= e($t['number']) ?> — <?= e($t['name'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Date *</label>
                            <input type="date" name="booking_date" value="<?= e($selectedDate) ?>" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Start Time *</label>
                            <input type="time" name="start_time" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">End Time *</label>
                            <input type="time" name="end_time" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Customer Name *</label>
                        <input type="text" name="customer_name" required
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="Customer name">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Phone</label>
                            <input type="text" name="customer_phone"
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                                   placeholder="Phone number">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Players</label>
                            <input type="number" name="players_count" value="2" min="1" max="20"
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none resize-none"
                                  placeholder="Special requests..."></textarea>
                    </div>

                    <?php if (user_can('payments.manage')): ?>
                    <!-- Advance payment -->
                    <div class="rounded-xl border border-dashed border-white/15 p-4 space-y-3"
                         x-data="{ advance: false }">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" x-model="advance" class="w-4 h-4 rounded accent-emerald-500">
                            <span class="text-slate-200 font-medium">Take advance / deposit now</span>
                            <span class="text-[11px] text-slate-500">(optional — can also be paid later)</span>
                        </label>
                        <div x-show="advance" x-cloak class="space-y-3 pt-1">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount (Rs) *</label>
                                    <input type="number" name="advance_amount" min="1" step="1" value="0"
                                           class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Method *</label>
                                    <select name="advance_method" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                                        <option value="cash">Cash</option>
                                        <option value="jazzcash">JazzCash</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="card">Card</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 mb-1.5">Transaction ref (for JazzCash / transfer)</label>
                                <input type="text" name="transaction_ref" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                                       placeholder="e.g. JazzCash TID 88XXXXXX">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">Create Booking</button>
                        <button type="button" @click="showBookingModal = false" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pay modal -->
    <div id="payModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('payModal').classList.add('hidden')"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-ink-800 border border-white/10 shadow-2xl p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-white">Record Payment</h3>
                <button type="button" onclick="document.getElementById('payModal').classList.add('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-slate-500 mb-4">Add a payment for this booking — cash, JazzCash, card or transfer. You can settle now and top up later.</p>
            <form method="POST" action="" id="payForm" class="space-y-4">
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Amount (Rs) *</label>
                            <input type="number" name="amount" id="pay-amount" min="1" step="1" required
                                   class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Method *</label>
                            <select name="method" id="pay-method" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                                <option value="cash">Cash</option>
                                <option value="jazzcash">JazzCash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Transaction ref (for JazzCash / transfer)</label>
                        <input type="text" name="transaction_ref" id="pay-ref" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="e.g. JazzCash TID 88XXXXXX">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
                        <input type="text" name="notes" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="optional">
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">Record Payment</button>
                        <button type="button" onclick="document.getElementById('payModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
function openPayModal(id, name) {
    document.getElementById('payForm').action = '<?= e(url('/bookings')) ?>/' + id + '/payment';
    document.getElementById('pay-amount').value = 0;
    document.getElementById('pay-ref').value = '';
    document.getElementById('payModal').classList.remove('hidden');
}
function shareBooking(el) {
    const name = el.dataset.name || 'customer';
    const phone = (el.dataset.phone || '').replace(/\D+/g, '');
    const clubNo = <?= json_encode(preg_replace('/\D+/', '', \App\Services\SettingsService::clubPhone())) ?>;
    const clubName = <?= json_encode(\App\Services\SettingsService::clubName()) ?>;
    const text = 'Booking reminder — ' + clubName + '\n' +
        'Customer: ' + name + '\n' +
        'Table #' + (el.dataset.table || '') + '\n' +
        'Date: ' + (el.dataset.date || '') + '\n' +
        'Time: ' + el.dataset.start + ' – ' + el.dataset.end + '\n' +
        'Players: ' + (el.dataset.players || '') +
        '\n\nReply to confirm or change. Thank you!';
    const waNo = phone ? phone : clubNo;
    window.open('https://wa.me/' + waNo + '?text=' + encodeURIComponent(text), '_blank');
    return false;
}
</script>
