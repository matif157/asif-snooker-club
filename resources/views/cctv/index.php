<?php
/** @var array $cameras, $tables */
/** @var bool $canManage */
/** @var string $serverUrl */
use App\Controllers\CameraController;
?>

<div class="space-y-6 fade-in">

    <!-- Page Title -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">CCTV</h1>
            <p class="text-sm text-slate-400 mt-1">Live camera grid. Streams are served by the local media server at <code class="text-slate-500"><?= e($serverUrl) ?></code>.</p>
        </div>
        <?php if ($canManage): ?>
            <button onclick="document.getElementById('addCameraModal').classList.remove('hidden')" class="btn-primary self-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Add Camera
            </button>
        <?php endif; ?>
    </div>

    <?php if ($errors = flash('cctv_errors')): ?>
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 px-4 py-3 space-y-1">
            <?php foreach ($errors as $err): ?>
                <p class="text-sm text-rose-300">• <?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Live grid -->
    <?php if (empty($cameras)): ?>
        <div class="card p-10 text-center">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-white/[0.04] border border-white/10 flex items-center justify-center mb-4 text-slate-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-white font-semibold mb-1">No cameras configured</h3>
            <p class="text-sm text-slate-500">Add your first camera to see it live in the grid.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($cameras as $cam): ?>
                <?php $stream = CameraController::streamUrl($serverUrl, $cam['stream_name']); ?>
                <?php $hls    = CameraController::hlsUrl($serverUrl, $cam['stream_name']); ?>
                <?php $live   = $streamMode === 'live' && $hls; ?>
                <div class="card p-3 group">
                    <div class="relative rounded-xl overflow-hidden bg-ink-900 aspect-video ring-1 ring-white/10">
                        <?php if ((int) $cam['enabled'] && $stream): ?>
                            <?php if ($live): ?>
                                <video class="camera-video w-full h-full object-cover" muted autoplay playsinline
                                       data-hls-src="<?= e($hls) ?>"></video>
                                <div class="hidden absolute inset-0 flex-col items-center justify-center text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <p class="text-xs">No signal</p>
                                </div>
                            <?php else: ?>
                                <img src="<?= e($stream) ?>"
                                     alt="<?= e($cam['name']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="hidden absolute inset-0 flex-col items-center justify-center text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    <p class="text-xs">No signal</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <p class="text-xs"><?= (int) $cam['enabled'] ? 'Camera offline / no stream configured' : 'Camera disabled' ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ((int) $cam['enabled'] && $stream): ?>
                            <span class="absolute top-2 left-2 flex items-center gap-1.5 rounded-md bg-black/60 backdrop-blur px-2 py-1 text-[10px] text-emerald-300 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> LIVE
                            </span>
                            <?php if ($live && CameraController::playerUrl($serverUrl, $cam['stream_name'])): ?>
                                <button type="button" onclick="openLive('<?= e(CameraController::playerUrl($serverUrl, $cam['stream_name'])) ?>')"
                                        class="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-black/60 backdrop-blur px-2 py-1 text-[10px] text-sky-300 font-medium hover:text-sky-200 hover:bg-black/80 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>
                                    Fullscreen
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-between mt-3 px-1">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white"><?= e($cam['name']) ?></p>
                            <p class="text-xs text-slate-500 truncate">
                                <?= e($cam['location'] ?? $cam['stream_name'] ?? '—') ?>
                                <?php if (!empty($cam['table_number'])): ?>
                                    <span class="inline-flex items-center gap-1 ml-1.5 text-emerald-400 font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        Table #<?= e($cam['table_number']) ?>
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($canManage): ?>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" onclick="openEditCamera(<?= (int) $cam['id'] ?>)"
                                        class="text-slate-500 hover:text-sky-400 transition p-1" aria-label="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <form method="POST" action="<?= e(url('/cctv/' . (int) $cam['id'] . '/delete')) ?>"
                                      onsubmit="return confirm('Remove this camera?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-slate-500 hover:text-rose-400 transition p-1" aria-label="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($canManage): ?>
    <!-- Setup hint -->
    <div class="card p-5 sm:p-6">
        <h3 class="text-sm font-semibold text-white mb-2">Connecting your cameras</h3>
        <p class="text-sm text-slate-400 leading-relaxed mb-4">
            This app displays streams from a lightweight local media server that restreams your
            IP cameras as browser-friendly HTTP streams — no browser plugins or VLC required.
        </p>
        <ol class="space-y-3 text-sm text-slate-300 list-decimal list-inside">
            <li>
                Install a stream server (recommended: <a href="https://github.com/AlexxIT/go2rtc" target="_blank" rel="noopener" class="text-emerald-400 hover:text-emerald-300">go2rtc</a> or
                <a href="https://github.com/bluenviron/mediamtx" target="_blank" rel="noopener" class="text-emerald-400 hover:text-emerald-300">mediamtx</a>) on the machine that can reach your cameras.
            </li>
            <li>
                Configure each camera with its <code class="text-slate-400 bg-white/[0.04] px-1.5 py-0.5 rounded">rtsp://user:pass@ip:554/…</code> source and give the stream a name like <code class="text-slate-400 bg-white/[0.04] px-1.5 py-0.5 rounded">table01</code>.
            </li>
            <li>Add the camera here with that stream name and make sure <em>Enabled</em> is checked. Tiles pick up the feed automatically.</li>
        </ol>
        <p class="text-xs text-slate-500 mt-4">
            Media server address (default <code>http://127.0.0.1:1984</code>) can be changed under Settings → CCTV.
            Example go2rtc config for two cameras:
        </p>
        <pre class="mt-3 rounded-xl bg-ink-900 border border-white/10 p-4 text-xs text-slate-400 overflow-x-auto">streams:
  table01: rtsp://admin:pass@192.168.1.20:554/stream1
  tables_all: rtsp://admin:pass@192.168.1.21:554/stream1</pre>
    </div>
    <?php endif; ?>

</div>

<!-- Add Camera Modal -->
<?php if ($canManage): ?>
<div id="addCameraModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="document.getElementById('addCameraModal').classList.add('hidden')"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-ink-800 border border-white/10 shadow-2xl p-6 sm:p-7">
        <h3 class="text-lg font-semibold text-white mb-1">Add Camera</h3>
        <p class="text-xs text-slate-500 mb-5">Stream name must match the stream defined in your media server.</p>
        <form method="POST" action="<?= e(url('/cctv')) ?>" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Camera name *</label>
                <input name="name" required class="input" placeholder="Entrance">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Location</label>
                <input name="location" class="input" placeholder="Main entrance">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">RTSP source (optional)</label>
                <input name="rtsp_url" class="input" placeholder="rtsp://user:pass@192.168.1.20:554/stream1">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Stream name</label>
                <input name="stream_name" class="input" placeholder="table01" pattern="[A-Za-z0-9_-]{1,80}">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Assigned table</label>
                <select name="table_id" class="input">
                    <option value="">— None —</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= (int) $t['id'] ?>">#<?= (int) $t['number'] ?> — <?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-600 mt-1">Links this camera to a table so the command center can jump to it.</p>
            </div>
            <label class="flex items-center gap-2 text-sm cursor-pointer pt-1">
                <input type="checkbox" name="enabled" value="1" checked class="w-4 h-4 rounded accent-emerald-500">
                <span class="text-slate-300 font-medium">Enabled</span>
            </label>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Camera</button>
                <button type="button" onclick="document.getElementById('addCameraModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Camera Modal -->
<div id="editCameraModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeEditCamera()"></div>
    <div class="relative w-full max-w-md rounded-2xl bg-ink-800 border border-white/10 shadow-2xl p-6 sm:p-7">
        <h3 class="text-lg font-semibold text-white mb-1">Edit Camera</h3>
        <p class="text-xs text-slate-500 mb-5">Update stream details or which table this camera watches.</p>
        <form method="POST" action="" id="editCameraForm" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Camera name *</label>
                <input name="name" id="edit-name" required class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Location</label>
                <input name="location" id="edit-location" class="input">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">RTSP source (optional)</label>
                <input name="rtsp_url" id="edit-rtsp" class="input" placeholder="rtsp://user:pass@192.168.1.20:554/stream1">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Stream name</label>
                <input name="stream_name" id="edit-stream" class="input" placeholder="table01" pattern="[A-Za-z0-9_-]{1,80}">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-400 mb-1.5">Assigned table</label>
                <select name="table_id" id="edit-table" class="input">
                    <option value="">— None —</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= (int) $t['id'] ?>">#<?= (int) $t['number'] ?> — <?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-2 text-sm cursor-pointer pt-1">
                    <input type="checkbox" name="enabled" value="1" id="edit-enabled" checked class="w-4 h-4 rounded accent-emerald-500">
                    <span class="text-slate-300 font-medium">Enabled</span>
                </label>
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1.5">Sort</label>
                    <input type="number" name="sort_order" id="edit-sort" min="0" class="input">
                </div>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Save Changes</button>
                <button type="button" onclick="closeEditCamera()" class="btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
<?php if ($canManage): ?>
const camerasData = <?= json_encode(array_map(fn($c) => [
    'id'        => (int) $c['id'],
    'name'      => $c['name'],
    'location'  => $c['location'] ?? '',
    'rtsp_url'  => $c['rtsp_url'] ?? '',
    'stream'    => $c['stream_name'] ?? '',
    'table_id'  => (int) ($c['table_id'] ?? 0),
    'enabled'   => (int) $c['enabled'],
    'sort'      => (int) ($c['sort_order'] ?? 0),
], $cameras)) ?>;

function openEditCamera(id) {
    const cam = camerasData.find(c => c.id === id);
    if (!cam) return;
    const form = document.getElementById('editCameraForm');
    form.action = '<?= e(url('/cctv')) ?>/' + id;
    document.getElementById('edit-name').value = cam.name;
    document.getElementById('edit-location').value = cam.location;
    document.getElementById('edit-rtsp').value = cam.rtsp_url;
    document.getElementById('edit-stream').value = cam.stream;
    document.getElementById('edit-table').value = String(cam.table_id);
    document.getElementById('edit-enabled').checked = cam.enabled === 1;
    document.getElementById('edit-sort').value = cam.sort;
    document.getElementById('editCameraModal').classList.remove('hidden');
}
function closeEditCamera() {
    document.getElementById('editCameraModal').classList.add('hidden');
}
<?php endif; ?>
</script>

<?php if ($streamMode === 'live'): ?>
<!-- Fullscreen WebRTC player (go2rtc's own player page, HLS fallback built-in) -->
<div id="liveModal" class="hidden fixed inset-0 z-[70] flex items-center justify-center p-4" x-cloak>
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeLive()"></div>
    <div class="relative w-full max-w-4xl">
        <div class="rounded-2xl overflow-hidden bg-black ring-1 ring-white/15 shadow-2xl">
            <iframe id="liveFrame" src="about:blank" title="Live camera" allow="autoplay"
                    class="w-full aspect-video bg-black"></iframe>
        </div>
        <div class="flex items-center justify-between mt-3">
            <p class="text-sm text-slate-400">WebRTC when available, HLS otherwise (served by go2rtc).</p>
            <button onclick="closeLive()" class="btn-secondary">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1/dist/hls.min.js"></script>
<script>
function openLive(url) {
    document.getElementById('liveFrame').src = url;
    document.getElementById('liveModal').classList.remove('hidden');
}
function closeLive() {
    document.getElementById('liveModal').classList.add('hidden');
    document.getElementById('liveFrame').src = 'about:blank';
}

document.querySelectorAll('.camera-video').forEach(video => {
    const showPlaceholder = () => {
        video.style.display = 'none';
        if (video.nextElementSibling) video.nextElementSibling.style.display = 'flex';
    };
    video.addEventListener('error', showPlaceholder);
    video.addEventListener('stalled', showPlaceholder);
    if (window.Hls && Hls.isSupported()) {
        const hls = new Hls({ lowLatencyMode: true });
        hls.loadSource(video.dataset.hlsSrc);
        hls.attachMedia(video);
        hls.on(Hls.Events.ERROR, (_, data) => {
            if (data.fatal) showPlaceholder();
        });
        video.muted = true;
        video.play().catch(showPlaceholder);
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = video.dataset.hlsSrc;
        video.muted = true;
        video.play().catch(showPlaceholder);
    } else {
        showPlaceholder();
    }
});
</script>
<?php endif; ?>