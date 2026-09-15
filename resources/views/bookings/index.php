<?php
/** @var array $bookings, $tables, $upcoming */
/** @var string $selectedDate */
?>

<div class="space-y-6 fade-in" x-data="{ showBookingModal: false }">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Bookings</h1>
            <p class="text-sm text-slate-400 mt-1">Manage table reservations for <span class="text-emerald-400 font-semibold"><?= date('l, M j, Y', strtotime($selectedDate)) ?></span></p>
        </div>
        <button @click="showBookingModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Booking
        </button>
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
                                    </p>
                                    <?php if (!empty($b['customer_phone'])): ?>
                                        <a href="tel:<?= e(preg_replace('/\D+/', '', $b['customer_phone'])) ?>"
                                           class="text-xs text-slate-500 hover:text-emerald-400 transition">
                                            <?= e($b['customer_phone']) ?>
                                        </a>
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
                                        <?php if (in_array($b['status'] ?? '', ['requested', 'confirmed'])): ?>
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

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">Create Booking</button>
                        <button type="button" @click="showBookingModal = false" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
