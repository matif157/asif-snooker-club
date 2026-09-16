<?php
/** @var array $settings */
/** @var array $users */
/** @var array $audit */
/** @var array $backups */
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Settings</h1>
            <p class="text-sm text-slate-400 mt-1">Club config, staff accounts and the audit trail.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Club config -->
        <div class="lg:col-span-2 card p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Club Profile</h2>
            <form method="POST" action="<?= e(url('/settings')) ?>">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Club Name</label>
                        <input name="club_name" class="input" value="<?= e($settings['club_name'] ?? 'Asif Snooker Club') ?>" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Club Phone (Pakistani)</label>
                        <input name="club_phone" class="input" placeholder="+92 300 1234567"
                               value="<?= e($settings['club_phone'] ?? '') ?>" required>
                        <p class="text-xs text-slate-500 mt-1">Drives click-to-call & WhatsApp links.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Address</label>
                        <input name="club_address" class="input" value="<?= e($settings['club_address'] ?? 'D Ground, Faisalabad') ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Currency Symbol</label>
                        <input name="currency" class="input" value="<?= e($settings['currency'] ?? 'Rs') ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Business Hours</label>
                        <div class="flex items-center gap-2">
                            <input name="business_hours_open" type="time" class="input" value="<?= e($settings['business_hours_open'] ?? '16:00') ?>">
                            <span class="text-slate-500 text-xs">→</span>
                            <input name="business_hours_close" type="time" class="input" value="<?= e($settings['business_hours_close'] ?? '02:00') ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Default Hourly Rate (Rs)</label>
                        <input name="default_hourly_rate" type="number" class="input" min="0" step="50"
                               value="<?= e($settings['default_hourly_rate'] ?? '300') ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Minimum Charge (Rs)</label>
                        <input name="default_min_charge" type="number" class="input" min="0" step="50"
                               value="<?= e($settings['default_min_charge'] ?? '100') ?>">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">WhatsApp Template Message</label>
                        <textarea name="whatsapp_template" rows="2" class="input"
                                  placeholder="Assalam o Alaikum {name}! Thank you for choosing Asif Snooker Club."><?= e($settings['whatsapp_template'] ?? '') ?></textarea>
                        <p class="text-xs text-slate-500 mt-1">Use {name} as a placeholder for the customer name.</p>
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <button type="submit" class="btn-primary">Save Settings</button>
                </div>
            </form>
        </div>

        <!-- Audit trail -->
        <div class="card p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Recent Activity</h2>
            <div class="space-y-2.5 max-h-80 overflow-y-auto">
                <?php if (empty($audit)): ?>
                    <p class="text-slate-500 text-sm">No activity yet.</p>
                <?php else: foreach ($audit as $entry): ?>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="badge badge-violet"><?= e($entry['action']) ?></span>
                        <span class="text-slate-400 truncate"><?= e($entry['user_name'] ?? 'system') ?></span>
                        <span class="text-slate-600 ml-auto whitespace-nowrap"><?= date('H:i', strtotime($entry['created_at'] ?? 'now')) ?></span>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Pricing & peak hours -->
    <div class="card p-5 sm:p-6" id="pricing">
        <h2 class="text-lg font-semibold text-white mb-4">Pricing &amp; Peak Hours</h2>
        <p class="text-sm text-slate-400 mb-5">New hourly sessions are auto-labelled and priced by the active band. Peak/night bands can cross midnight.</p>
        <form method="POST" action="<?= e(url('/settings')) ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="peak_enabled" value="1" <?= (int) ($settings['peak_enabled'] ?? 1) === 1 ? 'checked' : '' ?> class="w-4 h-4 rounded accent-emerald-500">
                        <span class="text-slate-300 font-medium">Peak pricing</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Peak Start</label>
                    <input name="peak_start" type="time" class="input" value="<?= e($settings['peak_start'] ?? '19:00') ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Peak End</label>
                    <input name="peak_end" type="time" class="input" value="<?= e($settings['peak_end'] ?? '00:00') ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Peak Multiplier ×</label>
                    <input name="peak_rate_multiplier" type="number" step="0.05" min="1" class="input" value="<?= e($settings['peak_rate_multiplier'] ?? '1') ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Night Start</label>
                    <input name="night_start" type="time" class="input" value="<?= e($settings['night_start'] ?? '00:00') ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Night End</label>
                    <input name="night_end" type="time" class="input" value="<?= e($settings['night_end'] ?? '06:00') ?>">
                </div>
            </div>
            <div class="mt-3 text-xs text-slate-500">Night rate uses each table's set <code>night_rate</code> (Rs) when ≥ peak hours end; otherwise the standard hourly rate applies.</div>
            <div class="mt-5 flex items-center justify-between">
                <button type="submit" class="btn-primary">Save Pricing</button>
                <button type="button" onclick="alert('Current band: <?= e(\App\Services\RateService::detectBand(null, ['night_rate' => 0])) ?>')" class="btn-secondary text-xs">Check current band</button>
            </div>
        </form>
    </div>

    <!-- Appearance / theme & accent -->
    <div class="card p-5 sm:p-6" id="appearance">
        <h2 class="text-lg font-semibold text-white mb-4">Appearance</h2>
        <p class="text-sm text-slate-400 mb-5">Pick the club accent colour — it flows through buttons, badges, nav and charts instantly. Theme mode is stored per user account.</p>
        <form method="POST" action="<?= e(url('/settings')) ?>"
          x-data="{
            accent: '<?= e($settings['accent_color'] ?? '#10b981') ?>',
            mix(hex, target = '500') {
                const n = hex.replace('#','').match(/../g).map(x => parseInt(x,16));
                if (!n) return hex;
                const w = target === '400' ? 15 : target === '300' ? 30 : 0;
                const b = target === '600' ? 14 : 0;
                const ch = (c) => Math.round(c + (255 - c) * (w/100) - c * (b/100));
                const rgb = n.map(ch);
                const toHex = (v) => v.toString(16).padStart(2,'0');
                return ['#' + rgb.map(toHex).join(''), rgb.join(' ')];
            },
            applyAccent() {
                const rs = document.documentElement.style;
                ['300','400','500','600'].forEach(s => {
                    const [hex, rgb] = this.mix(this.accent, s);
                    rs.setProperty('--a-' + s, hex);
                    rs.setProperty('--a-rgb-' + s, rgb);
                });
            }
          }"
          x-effect="applyAccent()">
            <?= csrf_field() ?>
            <input type="hidden" name="accent_color" :value="accent">
            <div class="flex items-center gap-3">
                <?php $swatches = [
                    '#10b981' => 'Emerald',
                    '#0f9d6f' => 'Baize',
                    '#8b5cf6' => 'Violet',
                    '#0ea5e9' => 'Sky',
                    '#f43f5e' => 'Rose',
                    '#f59e0b' => 'Gold',
                ]; ?>
                <?php foreach ($swatches as $hex => $label): ?>
                    <button type="button" @click="accent='<?= $hex ?>'"
                            class="w-9 h-9 rounded-full border-2 transition-all flex items-center justify-center"
                            :class="accent === '<?= $hex ?>' ? 'border-white scale-110 shadow-lg' : 'border-white/20 hover:border-white/50'"
                            style="background:<?= $hex ?>" :title="'<?= $label ?>'">
                        <svg x-show="accent === '<?= $hex ?>'" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                <?php endforeach; ?>
                <label class="flex items-center gap-2 ml-2 cursor-pointer">
                    <input type="color" :value="accent" @input="accent=$event.target.value" class="w-9 h-9 rounded-lg bg-transparent border border-white/20 cursor-pointer">
                    <span class="text-xs text-slate-400">Custom</span>
                </label>
            </div>
            <!-- Live accent preview -->
            <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3" x-data>
                <div class="rounded-xl px-4 py-3 text-white text-sm font-semibold flex items-center justify-center transition-all" style="background:var(--a-500)">Save Button</div>
                <div class="rounded-xl px-4 py-3 text-sm font-semibold transition-all" style="background:color-mix(in srgb, var(--a-500) 14%, transparent);color:var(--a-400)">Active Badge</div>
                <div class="rounded-xl px-4 py-3 text-sm transition-all" style="border:1px solid color-mix(in srgb, var(--a-500) 50%, transparent);color:var(--a-400)">Focused Input</div>
                <div class="rounded-xl px-4 py-3 text-sm transition-all" style="color:var(--a-400)">
                    <span class="inline-block w-2.5 h-2.5 rounded-full mr-1.5" style="background:var(--a-400)"></span>Live Status
                </div>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <p class="text-xs text-slate-500">Theme preference is stored on your account.</p>
            </div>
            <div class="mt-3" x-data="{ pref: '<?= e($userTheme ?? 'dark') ?>' }">
                <p class="text-xs font-medium text-slate-400 mb-2">Theme mode</p>
                <div class="inline-flex rounded-xl bg-ink-800 border border-white/10 p-1">
                    <?php foreach (['dark' => '🌙 Dark', 'light' => '☀️ Light', 'auto' => '🔄 Auto'] as $val => $label): ?>
                        <button type="button" @click="pref='<?= $val ?>'; apiPost('/theme',{theme: pref}).then(()=>{ location.reload(); })"
                                :class="pref === '<?= $val ?>' ? 'bg-emerald-500 text-white' : 'text-slate-400 hover:text-white'"
                                class="px-4 py-1.5 text-xs font-semibold rounded-lg transition">
                            <?= $label ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <button type="submit" class="btn-primary">Save Appearance</button>
                <a href="/settings" class="text-xs text-slate-500 hover:text-slate-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Database backups -->
    <div class="card p-5 sm:p-6" id="backups">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-white">Database Backups</h2>
                <p class="text-sm text-slate-400 mt-1">Portable SQL dumps stored locally — download anytime, last 20 kept.</p>
            </div>
            <form method="POST" action="<?= e(url('/settings/backup')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Backup Now
                </button>
            </form>
        </div>
        <?php if (empty($backups)): ?>
            <p class="text-sm text-slate-500">No backups yet — click "Backup Now" to create the first one.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>File</th><th>Size</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($backups as $b): ?>
                        <tr>
                            <td class="font-mono text-xs text-slate-300"><?= e($b['name']) ?></td>
                            <td class="text-slate-400"><?= number_format(round($b['size'] / 1024)) ?> KB</td>
                            <td class="text-slate-400"><?= e(date('M j, g:i A', $b['time'])) ?></td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <form method="POST" action="<?= e(url('/settings/restore')) ?>" class="inline"
                                          onsubmit="return confirm('Restore the ENTIRE database from <?= e(addslashes($b['name'])) ?>? Current data will be replaced (a safety backup is taken first).');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="name" value="<?= e($b['name']) ?>">
                                        <button type="submit" class="text-xs text-amber-400 hover:text-amber-300 font-medium">Restore</button>
                                    </form>
                                    <a href="<?= e(url('/settings/backups/' . $b['name'])) ?>" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">Download</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/settings/restore/upload')) ?>" enctype="multipart/form-data"
              class="mt-5 flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-dashed border-white/10 bg-white/[0.02] p-4"
              onsubmit="return confirm('Restore the ENTIRE database from the uploaded file? Current data will be replaced (a safety backup is taken first).');">
            <?= csrf_field() ?>
            <label class="text-xs text-slate-400 shrink-0">Restore from upload:</label>
            <input type="file" name="backup" accept=".sql" class="text-xs text-slate-300 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-white/5 file:text-emerald-300 file:text-xs file:cursor-pointer">
            <button type="submit" class="btn-secondary text-xs !py-1.5 shrink-0">Restore Upload</button>
        </form>
    </div>
    <div class="card p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold text-white">Staff Accounts</h2>
            <button onclick="document.getElementById('addStaffModal').classList.remove('hidden')" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Staff
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="font-medium text-white"><?= e($user['name']) ?></td>
                        <td class="text-slate-300"><?= e($user['email']) ?></td>
                        <td class="text-slate-300"><?= e($user['phone'] ?? '—') ?></td>
                        <td><span class="badge badge-violet"><?= e($user['role']) ?></span></td>
                        <td>
                            <form method="POST" action="<?= e(url('/settings/users/' . (int) $user['id'] . '/update')) ?>"
                                  class="inline-flex items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="name" value="<?= e($user['name']) ?>">
                                <input type="hidden" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                                <input type="hidden" name="role" value="<?= e($user['role']) ?>">
                                <select name="status" class="input !py-1.5 !text-xs"
                                        onchange="this.form.submit()" <?= (int) $user['id'] === 1 ? 'disabled' : '' ?>>
                                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </form>
                        </td>
                        <td class="text-xs text-slate-500"><?= e($user['last_login_at'] ? date('d M, H:i', strtotime($user['last_login_at'])) : 'never') ?></td>
                        <td>
                            <form method="POST" action="<?= e(url('/settings/users/' . (int) $user['id'] . '/update')) ?>" class="inline-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="name" value="<?= e($user['name']) ?>">
                                <input type="hidden" name="phone" value="<?= e($user['phone'] ?? '') ?>">
                                <input type="hidden" name="status" value="<?= e($user['status']) ?>">
                                <select name="role" class="input !py-1.5 !text-xs" onchange="this.form.submit()" <?= (int) $user['id'] === 1 ? 'disabled' : '' ?>>
                                    <?php foreach (['owner','admin','eco','counter','staff','auditor'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $user['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reminders & notifications -->
    <div class="card p-5 sm:p-6" id="reminders">
        <h2 class="text-lg font-semibold text-white mb-1">Reminders &amp; Notifications</h2>
        <p class="text-sm text-slate-400 mb-5">
            Scheduled WhatsApp reminders for upcoming bookings and outstanding balances.
            Reviewed under <a href="<?= e(url('/reminders')) ?>" class="text-emerald-400 hover:underline">Reminders Center</a>
            and auto-batched hourly by the cron job (<code class="text-slate-500">database/remind.php --run</code>).
        </p>
        <form method="POST" action="<?= e(url('/settings')) ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="reminder_enabled" value="1" <?= (int) ($settings['reminder_enabled'] ?? 1) === 1 ? 'checked' : '' ?> class="w-4 h-4 rounded accent-emerald-500">
                        <span class="text-slate-300 font-medium">Enabled</span>
                    </label>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Booking reminder window (min before start)</label>
                    <input name="reminder_horizon_min" type="number" min="15" step="15" class="input" value="<?= e($settings['reminder_horizon_min'] ?? '120') ?>">
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Booking reminder template</label>
                    <textarea name="booking_reminder_template" rows="4" class="input"><?= e($settings['booking_reminder_template'] ?? '') ?></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">Placeholders: {name} {club} {date} {time} {table}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Outstanding reminder template</label>
                    <textarea name="outstanding_reminder_template" rows="4" class="input"><?= e($settings['outstanding_reminder_template'] ?? '') ?></textarea>
                    <p class="text-[11px] text-slate-500 mt-1">Placeholders: {name} {club} {currency} {amount}</p>
                </div>
            </div>
            <div class="mt-5 flex items-center justify-between">
                <button type="submit" class="btn-primary">Save Reminders</button>
                <a href="<?= e(url('/reminders')) ?>" class="btn-secondary text-xs">Open Reminders Center</a>
            </div>
        </form>
    </div>

    <!-- Customer custom fields -->
    <div class="card p-5 sm:p-6" id="customer-fields">
        <h2 class="text-lg font-semibold text-white mb-1">Customer Fields</h2>
        <p class="text-sm text-slate-400 mb-5">
            Add up to five custom fields on the customer profile (e.g. Member Since, Nickname, Sponsor).
            Leave a label blank to hide that field. Values are filled per customer on their profile.
        </p>
        <form method="POST" action="<?= e(url('/settings')) ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1.5">Field <?= $i ?> label</label>
                        <input name="custom_field_<?= $i ?>_label" class="input" maxlength="60"
                               placeholder="e.g. Member Since"
                               value="<?= e($settings['custom_field_' . $i . '_label'] ?? '') ?>">
                    </div>
                <?php endfor; ?>
            </div>
            <div class="mt-5">
                <button type="submit" class="btn-primary">Save Fields</button>
            </div>
        </form>
    </div>

    <!-- CCTV -->
    <div class="card p-5 sm:p-6" id="cctv">
        <h2 class="text-lg font-semibold text-white mb-1">CCTV</h2>
        <p class="text-sm text-slate-400 mb-5">
            Address of the local media server powering the
            <a href="<?= e(url('/cctv')) ?>" class="text-emerald-400 hover:underline">live camera grid</a>.
        </p>
        <form method="POST" action="<?= e(url('/settings')) ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Media server URL</label>
                    <input name="cctv_server_url" class="input" placeholder="http://127.0.0.1:1984"
                           value="<?= e($settings['cctv_server_url'] ?? 'http://127.0.0.1:1984') ?>">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Grid stream mode</label>
                    <select name="cctv_stream_mode" class="input">
                        <option value="img" <?= ($settings['cctv_stream_mode'] ?? 'img') === 'img' ? 'selected' : '' ?>>Snapshot image (lightweight)</option>
                        <option value="live" <?= ($settings['cctv_stream_mode'] ?? 'img') === 'live' ? 'selected' : '' ?>>Live video (HLS + WebRTC fullscreen)</option>
                    </select>
                </div>
            </div>
            <div class="mt-5">
                <button type="submit" class="btn-primary">Save CCTV</button>
            </div>
        </form>
    </div>

    <!-- Roles & permissions -->
    <?php
        $editableRoles = ['eco', 'counter', 'staff', 'auditor'];
        $roleMap = [];
        foreach ($rolePerms as $rp) {
            $roleMap[$rp['role']][] = (int) $rp['permission_id'];
        }
        $permByName = [];
        foreach ($permissions as $p) {
            $permByName[$p['name']] = $p;
        }
        $grouped = [];
        foreach ($permissions as $p) {
            $domain = explode('.', $p['name'])[0];
            $grouped[$domain][] = $p;
        }
    ?>
    <div class="card p-5 sm:p-6" id="roles">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
            <div>
                <h2 class="text-lg font-semibold text-white">Roles &amp; Permissions</h2>
                <p class="text-sm text-slate-400 mt-1">Tick what each role can do. <strong class="text-slate-300">Owner &amp; Admin</strong> always keep full access (locked).</p>
            </div>
        </div>
        <form method="POST" action="<?= e(url('/settings/roles')) ?>">
            <?= csrf_field() ?>
            <div class="overflow-x-auto mt-5">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="min-w-[200px]">Permission</th>
                            <?php foreach ($editableRoles as $r): ?>
                                <th class="text-center min-w-[90px]"><?= ucfirst($r) ?></th>
                            <?php endforeach; ?>
                            <th class="text-center text-slate-600 min-w-[90px]">Owner / Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($grouped as $domain => $perms): ?>
                        <tr>
                            <td colspan="6" class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-emerald-400/80 bg-transparent"><?= e($domain) ?></td>
                        </tr>
                        <?php foreach ($perms as $p): ?>
                            <tr>
                                <td>
                                    <div class="text-slate-200 font-medium"><?= e($p['name']) ?></div>
                                    <div class="text-xs text-slate-500"><?= e($p['description'] ?? '') ?></div>
                                </td>
                                <?php foreach ($editableRoles as $r): ?>
                                    <td class="text-center">
                                        <label class="inline-flex items-center justify-center cursor-pointer">
                                            <input type="checkbox" name="perms[<?= $r ?>][]" value="<?= e($p['name']) ?>"
                                                   class="w-4 h-4 rounded accent-emerald-500"
                                                   <?= in_array((int) $p['id'], $roleMap[$r] ?? [], true) ? 'checked' : '' ?>>
                                        </label>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center"><span class="text-slate-600">🔒</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-5 flex items-center justify-between">
                <p class="text-xs text-slate-500">Changes are audited and can be reverted from this screen anytime.</p>
                <button type="submit" class="btn-primary">Save Permissions</button>
            </div>
        </form>
    </div>

</div>

<!-- Add Staff Modal -->
<div id="addStaffModal" class="modal-overlay hidden" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="modal-card">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-white">Add Staff Account</h3>
            <button onclick="document.getElementById('addStaffModal').classList.add('hidden')" class="text-slate-500 hover:text-white text-xl">&times;</button>
        </div>
        <form method="POST" action="<?= e(url('/settings/users/create')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Full Name</label>
                <input name="name" class="input" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Email</label>
                <input name="email" type="email" class="input" required>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Phone</label>
                <input name="phone" class="input" placeholder="03XXXXXXXXX">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Role</label>
                    <select name="role" class="input">
                        <option value="eco">ECO</option>
                        <option value="counter">Counter</option>
                        <option value="staff">Staff</option>
                        <option value="auditor">Auditor</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Password</label>
                    <input name="password" class="input" placeholder="auto-generated">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addStaffModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>