<?php
/** @var string $audience, $message */
/** @var array $customers */
/** @var bool $submitted */
$audiences = [
    'active'      => 'All active customers',
    'outstanding' => 'Customers with outstanding balance',
    'recent'      => 'Visited in the last 30 days',
    'member'      => 'Members & VIPs',
];
?>
<div class="space-y-6 fade-in">

    <div>
        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">WhatsApp Broadcast</h1>
        <p class="text-sm text-slate-400 mt-1">Compose one message, preview tailor-made links for each customer, then send one by one from WhatsApp.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Compose -->
        <div class="card p-6 lg:col-span-1">
            <h3 class="text-sm font-semibold text-white mb-4">Compose Message</h3>
            <form method="GET" action="<?= e(url('/customers/broadcast')) ?>" class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Audience *</label>
                    <select name="audience" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                        <?php foreach ($audiences as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $audience === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Message *</label>
                    <textarea name="message" rows="6" required
                              class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-4 py-2.5 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none resize-none"
                              placeholder="Type your message here..."><?= e($message) ?></textarea>
                    <p class="text-[11px] text-slate-500 mt-1.5">Use <code class="text-emerald-400">{name}</code> to personalize with each customer's name.</p>
                </div>
                <button type="submit" name="preview" value="1" class="btn-primary w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/></svg>
                    Generate Links
                </button>
            </form>
        </div>

        <!-- Results -->
        <div class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-white">Generated Links</h3>
                <?php if ($submitted): ?>
                    <span class="badge badge-emerald"><?= count($customers) ?> customers</span>
                <?php endif; ?>
            </div>

            <?php if (!$submitted): ?>
                <div class="text-center py-14">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-10.68 6.01L4 18l1.08-4.07A7 7 0 1119 11zM13 11h2m-6 0h1"/></svg>
                    <p class="text-sm text-slate-400">Choose an audience, type your message, and generate individual WhatsApp links.</p>
                </div>
            <?php elseif (empty($customers)): ?>
                <p class="text-sm text-slate-500 py-8 text-center">No customers match this audience with a phone number.</p>
            <?php else: ?>
                <div class="flex flex-wrap gap-2 mb-4">
                    <a class="btn-secondary text-xs" href="#" onclick="copyAllLinks(event, '<?= $audience ?>')">Copy all links</a>
                    <a class="btn-secondary text-xs" href="<?= e(url('/customers/export?prefill=' . rawurlencode($message) . '&audience=' . rawurlencode($audience))) ?>">Export to CSV</a>
                </div>
                <div class="overflow-y-auto max-h-[520px] border border-white/[0.06] rounded-xl divide-y divide-white/[0.06]">
                    <?php foreach ($customers as $i => $c): ?>
                        <div class="flex items-center justify-between gap-3 px-4 py-3 text-sm bg-white/[0.02]">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-white truncate"><?= e($c['name']) ?></p>
                                <p class="text-xs text-slate-500 truncate" data-personalized="<?= e($c['message']) ?>">
                                    <?= e($c['message']) ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <?php if ($c['outstanding'] > 0): ?>
                                    <span class="badge badge-amber">Rs <?= number_format($c['outstanding']) ?></span>
                                <?php endif; ?>
                                <a href="<?= e($c['waLink']) ?>" target="_blank"
                                   class="btn-primary !px-3 !py-1.5 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.94 6.45 17.5 2 12.04 2zm5.83 14.13c-.25.7-1.45 1.33-2.02 1.42-.52.08-1.17.11-1.88-.12-.43-.14-.99-.32-1.7-.63-3-1.3-4.95-4.32-5.1-4.52-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.17 1.04-2.47.27-.3.59-.37.79-.37h.57c.18 0 .43-.07.67.51.25.6.85 2.07.92 2.22.08.15.13.33.03.53-.1.2-.15.32-.3.5-.15.18-.32.4-.45.53-.15.15-.31.31-.13.61.18.3.79 1.3 1.7 2.11 1.17 1.04 2.15 1.37 2.46 1.52.3.15.48.13.66-.08.18-.2.76-.88.96-1.19.2-.3.4-.25.67-.15.28.1 1.75.83 2.05.98.3.15.5.22.57.35.08.13.08.73-.17 1.42z"/></svg>
                                    Send
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-[11px] text-slate-500 mt-3">Tip: "Copy all links" then paste once into a WhatsApp broadcast list.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyAllLinks(e) {
    const links = Array.from(document.querySelectorAll('a[href^="https://wa.me/"]'))
        .map(a => a.href)
        .filter((v, i, arr) => arr.indexOf(v) === i);
    if (!links.length) return;
    const ta = document.createElement('textarea');
    ta.value = links.join('\n');
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (_) {}
    document.body.removeChild(ta);
    e.preventDefault();
    alert('Copied ' + links.length + ' WhatsApp links. Open WhatsApp → New Broadcast → paste.');
}
</script>