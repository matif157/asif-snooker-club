<?php /** @var string|null $error */ ?>

<div class="space-y-6 fade-in max-w-2xl">

    <!-- Page Title -->
    <div class="flex items-center gap-3">
        <a href="/tables" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Add Table</h1>
            <p class="text-sm text-slate-400 mt-1">Register a new snooker table</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card p-6">
        <form method="POST" action="<?= e(url('/tables')) ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="number" class="block text-xs font-medium text-slate-400 mb-1.5">Table Number *</label>
                    <input type="text" id="number" name="number" required maxlength="10"
                           placeholder="e.g. 1, A3"
                           class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                </div>
                <div>
                    <label for="type" class="block text-xs font-medium text-slate-400 mb-1.5">Type</label>
                    <select id="type" name="type"
                            class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                        <option value="Standard">Standard</option>
                        <option value="VIP">VIP</option>
                        <option value="Tournament">Tournament</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="name" class="block text-xs font-medium text-slate-400 mb-1.5">Table Name *</label>
                <input type="text" id="name" name="name" required maxlength="120"
                       placeholder="e.g. Main Hall Table 1"
                       class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="hourly_rate" class="block text-xs font-medium text-slate-400 mb-1.5">Hourly Rate (Rs) *</label>
                    <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" min="0" required
                           placeholder="300"
                           class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                </div>
                <div>
                    <label for="min_charge" class="block text-xs font-medium text-slate-400 mb-1.5">Min Charge (Rs) *</label>
                    <input type="number" id="min_charge" name="min_charge" step="0.01" min="0" required
                           placeholder="100"
                           class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                </div>
            </div>

            <div>
                <p class="text-[11px] uppercase tracking-wider text-slate-500 font-medium mb-2">Optional Rates</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="frame_rate" class="block text-xs font-medium text-slate-400 mb-1.5">Frame Rate (Rs)</label>
                        <input type="number" id="frame_rate" name="frame_rate" step="0.01" min="0"
                               placeholder="0"
                               class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                    </div>
                    <div>
                        <label for="vip_rate" class="block text-xs font-medium text-slate-400 mb-1.5">VIP Rate (Rs)</label>
                        <input type="number" id="vip_rate" name="vip_rate" step="0.01" min="0"
                               placeholder="0"
                               class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                    </div>
                    <div>
                        <label for="night_rate" class="block text-xs font-medium text-slate-400 mb-1.5">Night Rate (Rs)</label>
                        <input type="number" id="night_rate" name="night_rate" step="0.01" min="0"
                               placeholder="0"
                               class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
                    </div>
                </div>
            </div>

            <div>
                <label for="location" class="block text-xs font-medium text-slate-400 mb-1.5">Location</label>
                <input type="text" id="location" name="location"
                       placeholder="e.g. Main Hall, Mezzanine"
                       class="w-full bg-ink-800/80 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/60 focus:border-emerald-500/50 transition">
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Save Table
                </button>
                <a href="/tables" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
