<?php ?>

<div class="space-y-6 fade-in">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">New Tournament</h1>
            <p class="text-sm text-slate-400 mt-1">Set up a knockout tournament for the club</p>
        </div>
        <a href="<?= e(url('/tournaments')) ?>" class="btn-secondary">← Back</a>
    </div>

    <form method="POST" action="<?= e(url('/tournaments')) ?>" class="card p-5 sm:p-6 max-w-2xl space-y-5">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Tournament name *</label>
            <input type="text" name="name" required placeholder="e.g. Asif Club Ramadan Knockout 2026"
                   class="input">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Entry fee (Rs)</label>
                <input type="number" name="entry_fee" min="0" step="50" value="0" class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Best of (frames)</label>
                <select name="best_of" class="input">
                    <option value="1">1 frame</option>
                    <option value="3" selected>Best of 3</option>
                    <option value="5">Best of 5</option>
                    <option value="7">Best of 7</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Status</label>
                <select name="status" class="input">
                    <option value="draft" selected>Draft</option>
                    <option value="open">Open (accepting entries)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Start date</label>
                <input type="date" name="start_date" class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">End date</label>
                <input type="date" name="end_date" class="input">
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Prize details</label>
            <input type="text" name="prize_details" placeholder="e.g. Winner Rs 25,000 — Runner-up Rs 10,000"
                   class="input">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-400 mb-1.5">Notes</label>
            <textarea name="notes" rows="3" class="input" placeholder="Rules, format notes…"></textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">Create Tournament</button>
            <a href="<?= e(url('/tournaments')) ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>