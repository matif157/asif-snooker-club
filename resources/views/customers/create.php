<?php
/** @var array|null $customer */
/** @var bool $isEdit */
$isEdit   = $isEdit ?? false;
$customer = $customer ?? null;
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <a href="<?= e(url('/customers')) ?>" class="hover:text-emerald-400 transition">Customers</a>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-400"><?= $isEdit ? 'Edit Customer' : 'Add Customer' ?></span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                <?= $isEdit ? 'Edit Customer' : 'Add New Customer' ?>
            </h1>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card p-6 sm:p-8 max-w-2xl">
        <form method="POST"
              action="<?= e($isEdit && $customer ? url('/customers/' . $customer['id']) : url('/customers')) ?>"
              class="space-y-5">
            <?= csrf_field() ?>
            <?php if ($isEdit && $customer): ?>
                <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-medium text-slate-400 mb-1.5">Full Name *</label>
                <input type="text" id="name" name="name" required
                       value="<?= e(old('name', $customer['name'] ?? '')) ?>"
                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition"
                       placeholder="Enter customer name">
            </div>

            <!-- Phone & WhatsApp -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="phone" class="block text-xs font-medium text-slate-400 mb-1.5">Phone Number</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">+92</span>
                        <input type="text" id="phone" name="phone"
                               value="<?= e(old('phone', $customer['phone'] ?? '')) ?>"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white pl-14 pr-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition"
                               placeholder="3XXXXXXXXX">
                    </div>
                </div>
                <div>
                    <label for="whatsapp" class="block text-xs font-medium text-slate-400 mb-1.5">WhatsApp Number</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">+92</span>
                        <input type="text" id="whatsapp" name="whatsapp"
                               value="<?= e(old('whatsapp', $customer['whatsapp'] ?? '')) ?>"
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white pl-14 pr-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition"
                               placeholder="3XXXXXXXXX">
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-medium text-slate-400 mb-1.5">Email Address</label>
                <input type="email" id="email" name="email"
                       value="<?= e(old('email', $customer['email'] ?? '')) ?>"
                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition"
                       placeholder="customer@example.com">
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-xs font-medium text-slate-400 mb-1.5">Category</label>
                <div class="grid grid-cols-4 gap-2">
                    <?php
                    $cats = [
                        'regular'    => ['label' => 'Regular',  'color' => 'slate'],
                        'vip'        => ['label' => 'VIP',      'color' => 'emerald'],
                        'member'     => ['label' => 'Member',   'color' => 'sky'],
                        'tournament' => ['label' => 'Tournament','color' => 'violet'],
                    ];
                    $currentCat = old('category', $customer['category'] ?? 'regular');
                    ?>
                    <?php foreach ($cats as $key => $meta): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="category" value="<?= $key ?>"
                                   <?= $currentCat === $key ? 'checked' : '' ?> class="peer hidden">
                            <div class="text-center py-2.5 rounded-xl border text-xs font-medium transition
                                        border-white/10 text-slate-400
                                        peer-checked:border-<?= $meta['color'] ?>-500/50 peer-checked:bg-<?= $meta['color'] ?>-500/10 peer-checked:text-<?= $meta['color'] ?>-400">
                                <?= $meta['label'] ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none resize-none transition"
                          placeholder="Preferences, playing level, or other notes..."><?= e(old('notes', $customer['notes'] ?? '')) ?></textarea>
            </div>

            <!-- Custom fields -->
            <?php $fieldLabels = array_map(fn($i) => (string) \App\Services\SettingsService::get("custom_field_{$i}_label", ''), range(1, 5)); ?>
            <?php if (array_filter($fieldLabels)): ?>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Extra Details</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($fieldLabels as $i => $label): ?>
                            <?php if (trim($label) === '') continue; ?>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1.5"><?= e($label) ?></label>
                                <input type="text" name="cf_<?= $i + 1 ?>"
                                       value="<?= e(old("cf_" . ($i + 1), $customer['cf_' . ($i + 1)] ?? '')) ?>"
                                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none transition">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-slate-600 mt-2">Field labels are configured under Settings → Customer Fields.</p>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-600">
                    Custom fields available — enable them under
                    <a href="<?= e(url('/settings#customer-fields')) ?>" class="text-emerald-400 hover:text-emerald-300">Settings → Customer Fields</a>.
                </p>
            <?php endif; ?>

            <!-- Portal access -->
            <div class="pt-4 border-t border-white/10">
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Member Portal Access</label>
                <?php $hasPin = $customer && !empty($customer['portal_pin']); ?>
                <p class="text-[11px] text-slate-500 mb-2">
                    Your customer can sign in at <span class="text-slate-300">/portal</span> with their
                    phone number and a 4-digit PIN to view their balance and book tables.
                    <?php if ($hasPin): ?>
                        Current status: <span class="text-emerald-400 font-semibold">PIN set</span>.
                    <?php else: ?>
                        Current status: <span class="text-amber-400 font-semibold">no PIN — portal disabled</span>.
                    <?php endif; ?>
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <input type="text" name="portal_pin" inputmode="numeric" pattern="\d{4}" maxlength="4"
                               value=""
                               class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none"
                               placeholder="<?= $isEdit ? 'New 4-digit PIN (leave blank to keep)' : 'Optional 4-digit PIN (e.g. 1234)' ?>">
                    </div>
                    <?php if ($hasPin && !empty($customer['whatsapp'])): ?>
                        <?php
                            $pinWaDigits = preg_replace('/\D+/', '', $customer['whatsapp']);
                            $pinWaText   = 'Salam ' . $customer['name'] . '! Your ' . \App\Services\SettingsService::clubName() . ' member portal PIN is ready. Sign in at ' . url('/portal') . ' with your phone number and 4-digit PIN to view your balance and book a table.';
                        ?>
                        <a href="https://wa.me/<?= e($pinWaDigits) ?>?text=<?= rawurlencode($pinWaText) ?>"
                           class="btn-secondary !py-2.5 text-xs" target="_blank" rel="noopener">
                            Send PIN via WhatsApp
                        </a>
                    <?php else: ?>
                        <p class="text-[11px] text-slate-600">PIN is stored as a secure hash. Set one here, then tell the customer.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-3">
                <button type="submit" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <?= $isEdit ? 'Update Customer' : 'Save Customer' ?>
                </button>
                <a href="<?= e(url('/customers')) ?>" class="btn-secondary">Cancel</a>
                <?php if ($isEdit && $customer): ?>
                    <a href="<?= e(url('/customers/' . $customer['id'])) ?>" class="btn-secondary ml-auto">View Profile</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
