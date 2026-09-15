/* ASIF SNOOKER CLUB — Client-side JS */

// Theme toggle
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.addEventListener('click', () => {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
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