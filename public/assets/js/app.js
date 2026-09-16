/* ASIF SNOOKER CLUB — Client-side JS */

// Theme toggle (persists per user on the server)
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.addEventListener('click', async () => {
            const html = document.documentElement;
            const next = html.classList.contains('dark') ? 'light' : 'dark';
            html.classList.toggle('dark', next === 'dark');
            html.dataset.theme = next;
            try {
                await apiPost('/theme', { theme: next });
            } catch (e) {}
        });
    }
});

// Clock
setInterval(() => {
    const el = document.getElementById('clock-live');
    if (el) {
        el.textContent = new Date().toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }
}, 1000);

// Format seconds into HH:MM:SS
function formatDuration(totalSeconds) {
    const h = Math.floor(totalSeconds / 3600);
    const m = Math.floor((totalSeconds % 3600) / 60);
    const s = totalSeconds % 60;
    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
}

// CSRF helper for AJAX
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function apiPost(url, data = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    });
    return response.json();
}

async function apiGet(url) {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });
    return response.json();
}

// Format currency
function formatCurrency(amount) {
    return 'Rs ' + Number(amount).toLocaleString('en-PK', {maximumFractionDigits: 0});
}

// Dashboard live KPI updates (kpis: [data-revenue], [data-sessions], ...) —
// lightweight polling, works on any hosting.
document.addEventListener('DOMContentLoaded', () => {
    const kpiRevenue = document.querySelector('[data-kpi="revenue"]');
    if (!kpiRevenue) return;

    async function syncKpis() {
        try {
            const res = await apiGet('/api/dashboard/stats');
            if (!res.success) return;

            const syncEl = document.getElementById('last-sync');
            if (syncEl) syncEl.textContent = 'just now';

            const set = (key, el) => {
                const node = document.querySelector('[data-kpi="' + key + '"]');
                if (node && res.data[key] !== undefined) node.textContent = res.data[key];
            };

            const revenue = document.querySelector('[data-kpi="revenue"]');
            if (revenue) revenue.textContent = 'Rs ' + Number(res.data.revenue || 0).toLocaleString();

            const sessions = document.querySelector('[data-kpi="sessions"]');
            if (sessions) sessions.textContent = res.data.sessions;

            const active = document.querySelector('[data-kpi="active_tables"]');
            if (active) active.textContent = res.data.active_tables;

            const profit = document.querySelector('[data-kpi="profit"]');
            if (profit) profit.textContent = 'Rs ' + Number(res.data.profit || 0).toLocaleString();
        } catch (e) {}
    }
    setInterval(syncKpis, 15000);
    syncKpis();
});