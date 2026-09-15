<?php
/** @var array $customers */
/** @var int $totalCustomers, $vipCount, $memberCount */
/** @var float $totalOutstanding */
?>

<div class="space-y-6 fade-in" x-data="{ search: '', showAddModal: false, showImportModal: false }">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Customers</h1>
            <p class="text-sm text-slate-400 mt-1">Manage your customer database and contacts</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(url('/customers/export')) ?>" class="btn-secondary !py-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </a>
            <button @click="showImportModal = true" class="btn-secondary !py-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import
            </button>
            <button @click="showAddModal = true" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Customer
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Total Customers</p>
            <p class="text-2xl font-bold text-white"><?= (int) $totalCustomers ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">VIP Customers</p>
            <p class="text-2xl font-bold text-emerald-400"><?= (int) $vipCount ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Members</p>
            <p class="text-2xl font-bold text-sky-400"><?= (int) $memberCount ?></p>
        </div>
        <div class="stat-card">
            <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-1">Outstanding</p>
            <p class="text-2xl font-bold text-rose-400">Rs <?= number_format((float) $totalOutstanding) ?></p>
        </div>
    </div>

    <!-- Search -->
    <div class="relative max-w-md">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="search" placeholder="Search by name, phone, or email..."
               class="w-full bg-ink-800 border border-white/10 rounded-xl text-sm text-white pl-10 pr-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
    </div>

    <!-- Customer Cards -->
    <div class="space-y-2.5">
        <?php if (empty($customers)): ?>
            <div class="card p-12 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M9 20H4v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-sm text-slate-400">No customers found</p>
                <button @click="showAddModal = true" class="btn-primary mt-4">Add First Customer</button>
            </div>
        <?php else: ?>
            <?php foreach ($customers as $c): ?>
                <?php
                    $catBadge = match($c['category'] ?? 'regular') {
                        'vip'        => 'badge-emerald',
                        'member'     => 'badge-sky',
                        'tournament' => 'badge-violet',
                        default      => 'badge-slate',
                    };
                    $catLabel = match($c['category'] ?? 'regular') {
                        'vip'        => 'VIP',
                        'member'     => 'Member',
                        'tournament' => 'Tournament',
                        default      => 'Regular',
                    };
                    $phoneDigits = preg_replace('/\D+/', '', $c['phone'] ?? '');
                    $waDigits    = preg_replace('/\D+/', '', $c['whatsapp'] ?? $c['phone'] ?? '');
                ?>
                <div class="card p-4 flex flex-col sm:flex-row sm:items-center gap-4 hover:border-white/[0.1] transition group">
                    <!-- Avatar -->
                    <div class="w-11 h-11 rounded-xl bg-ink-700 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-500/15 transition">
                        <span class="text-emerald-400 text-sm font-bold"><?= strtoupper(mb_substr(e($c['name'] ?? '?'), 0, 1)) ?></span>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="<?= e(url('/customers/' . $c['id'])) ?>" class="text-sm font-semibold text-white hover:text-emerald-400 transition">
                                <?= e($c['name'] ?? '') ?>
                            </a>
                            <span class="badge <?= $catBadge ?>"><?= $catLabel ?></span>
                        </div>
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-400">
                            <?php if (!empty($c['phone'])): ?>
                                <a href="tel:+<?= e($phoneDigits) ?>" class="inline-flex items-center gap-1 hover:text-emerald-400 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    <?= e($c['phone'] ?? '') ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($waDigits)): ?>
                                <a href="https://wa.me/<?= e($waDigits) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-emerald-400 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-6 text-right text-xs">
                        <div>
                            <p class="text-slate-500">Visits</p>
                            <p class="text-white font-semibold mt-0.5"><?= (int) ($c['total_visits'] ?? 0) ?></p>
                        </div>
                        <div>
                            <p class="text-slate-500">Outstanding</p>
                            <p class="font-semibold mt-0.5 <?= (float) ($c['outstanding_balance'] ?? 0) > 0 ? 'text-rose-400' : 'text-emerald-400' ?>">
                                Rs <?= number_format((float) ($c['outstanding_balance'] ?? 0)) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-slate-500">Last Visit</p>
                            <p class="text-white font-medium mt-0.5">
                                <?= !empty($c['last_visit_at']) ? date('M j', strtotime($c['last_visit_at'])) : '—' ?>
                            </p>
                        </div>
                    </div>

                    <!-- Edit Link -->
                    <a href="<?= e(url('/customers/' . $c['id'] . '/edit')) ?>"
                       class="hidden sm:inline-flex p-2 rounded-lg text-slate-500 hover:text-white hover:bg-white/5 transition" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Customer Modal -->
    <div x-show="showAddModal" x-cloak
         class="modal-overlay" x-transition.opacity
         @keydown.escape.window="showAddModal = false">
        <div class="modal-card" @click.stop x-transition.scale.95>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold text-white">Add New Customer</h2>
                    <button @click="showAddModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form method="POST" action="<?= e(url('/customers')) ?>" class="space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Name *</label>
                        <input type="text" name="name" required
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="Customer name">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">Phone</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">+92</span>
                                <input type="text" name="phone"
                                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white pl-14 pr-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                                       placeholder="3XXXXXXXXX">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1.5">WhatsApp</label>
                            <div class="relative">
                                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm">+92</span>
                                <input type="text" name="whatsapp"
                                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white pl-14 pr-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                                       placeholder="3XXXXXXXXX">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Email</label>
                        <input type="email" name="email"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="email@example.com">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Category</label>
                        <select name="category"
                                class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                            <option value="regular">Regular</option>
                            <option value="vip">VIP</option>
                            <option value="member">Member</option>
                            <option value="tournament">Tournament Player</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none resize-none"
                                  placeholder="Any notes about this customer..."></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="btn-primary">Save Customer</button>
                        <button type="button" @click="showAddModal = false" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="showImportModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         @click.self="showImportModal = false"
         @keydown.escape.window="showImportModal = false">
        <div class="card w-full max-w-md p-6 relative max-h-[90vh] overflow-y-auto">
            <button @click="showImportModal = false" class="absolute top-4 right-4 p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>

            <h3 class="text-lg font-semibold text-white mb-1">Import Customers</h3>
            <p class="text-sm text-slate-400 mb-5">Upload a CSV with columns: <code class="text-emerald-400">name, phone, whatsapp, email, category, notes</code>. Duplicate phones are skipped.</p>

            <form method="POST" action="<?= e(url('/customers/import')) ?>" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <div class="border-2 border-dashed border-white/10 rounded-xl p-6 text-center hover:border-emerald-500/40 transition">
                    <label for="csvFile" class="cursor-pointer block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-500 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="text-sm text-slate-300">Click to select CSV file</span>
                        <span class="block text-xs text-slate-500 mt-1">or drop it here</span>
                    </label>
                    <input id="csvFile" name="csv_file" type="file" accept=".csv,text/csv" class="hidden" required>
                </div>
                <div class="flex items-center gap-3 pt-5">
                    <button type="submit" class="btn-primary">Import CSV</button>
                    <button type="button" @click="showImportModal = false" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
