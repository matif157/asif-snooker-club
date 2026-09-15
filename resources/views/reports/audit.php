<?php
/** @var array $rows, $filters, $actions, $entities */
/** @var int $total, $page, $perPage */
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="space-y-6 fade-in">

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Audit Log</h1>
            <p class="text-sm text-slate-400 mt-1">Full trail of all business actions — <span class="text-emerald-400 font-semibold"><?= number_format($total) ?></span> events</p>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="card p-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Action</label>
                <select name="action" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-3 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                    <option value="">All actions</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= e($a) ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= e(ucfirst($a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Entity</label>
                <select name="entity" class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-3 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
                    <option value="">All entities</option>
                    <?php foreach ($entities as $ent): ?>
                        <option value="<?= e($ent) ?>" <?= $filters['entity'] === $ent ? 'selected' : '' ?>><?= e(ucfirst($ent)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">From</label>
                <input type="date" name="from" value="<?= e($filters['from']) ?>"
                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-3 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">To</label>
                <input type="date" name="to" value="<?= e($filters['to']) ?>"
                       class="w-full bg-ink-850 border border-white/10 rounded-xl text-sm text-white px-3 py-2.5 focus:ring-2 focus:ring-emerald-500/50 focus:outline-none">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary !px-4">Filter</button>
                <a href="<?= e(url('/reports/audit')) ?>" class="btn-secondary text-sm">Reset</a>
            </div>
        </div>
    </form>

    <!-- Entries -->
    <div class="card p-6">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Record</th>
                        <th>Details</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $log): ?>
                        <tr>
                            <td class="text-slate-400 whitespace-nowrap"><?= e(date('M j, g:i A', strtotime($log['created_at']))) ?></td>
                            <td class="font-medium text-white"><?= e($log['user_name'] ?? 'System') ?></td>
                            <td><span class="badge badge-<?= match ($log['action']) {
                                'session_started', 'session_ended' => 'green',
                                'payment_received'                => 'emerald',
                                'expense_approved', 'expense_created', 'expense_rejected' => 'amber',
                                'booking_created', 'booking_activated', 'booking_completed', 'booking_cancelled' => 'violet',
                                'settings_updated', 'user_created', 'user_updated', 'backup_created' => 'sky',
                                default                           => 'slate',
                            } ?>"><?= e(ucwords(str_replace('_', ' ', $log['action']))) ?></span></td>
                            <td class="text-slate-400"><?= e(ucfirst($log['entity'] ?? '—')) ?></td>
                            <td class="text-slate-400"><?= e($log['record_id'] ?? '—') ?></td>
                            <td class="text-xs text-slate-500 max-w-[260px] truncate">
                                <?php if ($log['new_value']): ?>
                                    <?php $decoded = json_decode($log['new_value'], true); ?>
                                    <?= e(is_array($decoded) ? 'Data captured' : $log['new_value']) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-500 text-xs"><?= e($log['ip_address'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="text-slate-500 text-center py-8">No audit entries match these filters</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-between mt-5 pt-4 border-t border-white/[0.06]">
                <p class="text-xs text-slate-500">Page <?= $page ?> of <?= $totalPages ?></p>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(url('/reports/audit?page=' . ($page - 1) . '&action=' . urlencode($filters['action']) . '&entity=' . urlencode($filters['entity']) . '&from=' . urlencode($filters['from']) . '&to=' . urlencode($filters['to']))) ?>" class="btn-secondary text-xs">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(url('/reports/audit?page=' . ($page + 1) . '&action=' . urlencode($filters['action']) . '&entity=' . urlencode($filters['entity']) . '&from=' . urlencode($filters['from']) . '&to=' . urlencode($filters['to']))) ?>" class="btn-secondary text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>