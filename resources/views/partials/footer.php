<?php if (!is_authenticated()) return; ?>
<footer class="py-4 px-6 text-center text-xs text-slate-600 border-t border-white/[0.04]">
    <?= e(\App\Services\SettingsService::clubName()) ?> &middot; <?= e(\App\Services\SettingsService::clubAddress()) ?> &middot; &copy; <?= date('Y') ?>
    &middot; <a href="<?= e(url('/portal')) ?>" class="text-slate-500 hover:text-emerald-400 transition" target="_blank">Member Portal</a>
</footer>