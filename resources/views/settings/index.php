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
                                <a href="<?= e(url('/settings/backups/' . $b['name'])) ?>" class="text-xs text-emerald-400 hover:text-emerald-300 font-medium">Download</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Staff management -->
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