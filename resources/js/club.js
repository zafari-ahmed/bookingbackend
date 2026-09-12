export function clubShell() {
    return {
        search: '',
        results: { members: [], bookings: [], courts: [] },
        searching: false,
        openSearch: false,
        notifications: [],
        unread: window.Club?.unread || 0,
        openNotes: false,
        toast: null,
        confirm: { open: false, title: '', message: '', extra: '', action: null },
        mobileNav: false,
        sidebarCollapsed: localStorage.getItem('sac.sidebar') === '1',

        init() {
            window.clubToast = (message, tone = 'ok') => this.showToast(message, tone);
            window.clubConfirm = (opts) => this.ask(opts);
            this.pingNotifications();
        },

        toggleSidebar() {
            this.sidebarCollapsed = ! this.sidebarCollapsed;
            localStorage.setItem('sac.sidebar', this.sidebarCollapsed ? '1' : '0');
        },

        async lookup() {
            if (this.search.trim().length < 2) {
                this.results = { members: [], bookings: [], courts: [] };
                this.openSearch = false;
                return;
            }
            this.searching = true;
            this.openSearch = true;
            const res = await fetch(`/search?q=${encodeURIComponent(this.search)}`, { headers: { Accept: 'application/json' } });
            this.results = await res.json();
            this.searching = false;
        },

        async pingNotifications() {
            const res = await fetch('/notifications', { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.notifications = data.items || [];
            this.unread = data.unread || 0;
        },

        async markAllRead() {
            await this.post('/notifications/read-all');
            this.notifications = this.notifications.map((n) => ({ ...n, read_at: new Date().toISOString() }));
            this.unread = 0;
        },

        ask({ title, message, extra = '', confirmText = 'Confirm', tone = 'danger' }) {
            return new Promise((resolve) => {
                this.confirm = { open: true, title, message, extra, confirmText, tone, action: resolve };
            });
        },

        settle(ok) {
            const done = this.confirm.action;
            this.confirm.open = false;
            if (done) done(ok);
        },

        showToast(message, tone = 'ok') {
            this.toast = { message, tone };
            setTimeout(() => { this.toast = null; }, 3200);
        },

        async post(url, body = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': window.Club.csrf,
                },
                body: JSON.stringify(body),
            });
            return res.json();
        },
    };
}

export function csrfHeaders(method = 'POST') {
    return {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.Club.csrf,
        },
    };
}

export function money(value) {
    return `Rs. ${Number(value || 0).toLocaleString('en-PK')}`;
}

export function timeLabel(value) {
    if (!value) return '';
    const [h, m] = value.split(':');
    const hour = Number(h);
    const suffix = hour >= 12 ? 'PM' : 'AM';
    const twelve = hour % 12 || 12;
    return `${twelve}:${String(m || '00').padStart(2, '0')}\u00A0${suffix}`;
}

export const durationOptions = [
    { mins: 30, label: '30 min' },
    { mins: 60, label: '1 hour' },
    { mins: 90, label: '1.5 hours' },
    { mins: 120, label: '2 hours' },
];

export function minutesBetween(start, end) {
    if (!start || !end) return 0;
    const [sh, sm] = String(start).slice(0, 5).split(':').map(Number);
    const [eh, em] = String(end).slice(0, 5).split(':').map(Number);
    let mins = (eh * 60 + em) - (sh * 60 + sm);
    if (mins <= 0) mins += 24 * 60;
    return mins;
}

export function durationLabel(start, end) {
    const mins = minutesBetween(start, end);
    if (mins <= 0) return '';
    if (mins < 60) return `${mins} min`;
    const hours = mins / 60;
    return hours === 1 ? '1 hour' : `${hours} hours`;
}
