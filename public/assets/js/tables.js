/* ASP tables grid — live updates via lightweight polling.
   Fallback designed for shared hosting (no persistent SSE workers). */

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('tables-grid');
    if (!grid) return;

    let lastSync = null;

    async function pollTables() {
        try {
            const res = await apiGet('/api/tables');
            if (!res.success || !res.data) return;

            res.data.forEach(t => {
                const tile = grid.querySelector('[data-table-id="' + t.id + '"]');
                if (!tile) return;

                // Update status
                tile.dataset.status = t.status;

                // Update timer / class modifiers
                tile.classList.remove('table-tile--available', 'table-tile--occupied',
                    'table-tile--reserved', 'table-tile--maintenance', 'table-tile--blocked');

                switch (t.status) {
                    case 'available':
                        tile.classList.add('table-tile--available', 'status-available');
                        break;
                    case 'occupied':
                        tile.classList.add('table-tile--occupied', 'status-occupied');
                        break;
                    case 'reserved':
                        tile.classList.add('table-tile--reserved', 'status-reserved');
                        break;
                    case 'maintenance':
                        tile.classList.add('table-tile--maintenance', 'status-maintenance');
                        break;
                    case 'blocked':
                        tile.classList.add('table-tile--blocked');
                        break;
                }

                // Update timer display for occupied tables
                const timer = tile.querySelector('.timer-display');
                if (timer) {
                    if (t.status === 'occupied' && t.elapsed !== undefined) {
                        timer.dataset.status = 'active';
                        timer.textContent = formatDuration(t.elapsed);
                    } else if (t.status !== 'occupied') {
                        timer.dataset.status = 'ended';
                    }
                }

                // Update the status badge label
                const badge = tile.querySelector('.badge');
                if (badge) {
                    const labels = {
                        available: 'Available',
                        occupied: 'Occupied',
                        reserved: 'Reserved',
                        maintenance: 'Maintenance',
                        blocked: 'Blocked',
                        offline: 'Offline'
                    };
                    badge.textContent = labels[t.status] || t.status;
                    badge.className = 'badge ' + badgeColorFor(t.status);
                }
            });

            const syncEl = document.getElementById('last-sync');
            if (syncEl) syncEl.textContent = 'just now';
            lastSync = Date.now();
        } catch (e) {
            // Silently ignore; next poll will retry
        }
    }

    function badgeColorFor(status) {
        switch (status) {
            case 'available': return 'badge-emerald';
            case 'occupied':  return 'badge-emerald';
            case 'reserved':  return 'badge-violet';
            default:          return 'badge-rose';
        }
    }

    setInterval(pollTables, 5000);
    pollTables();

    // Hide Start/End buttons when status changes via poll
    // and alert on new occupied sessions (simple, non-intrusive)
    document.addEventListener('visibilitychange', () => pollTables());
});