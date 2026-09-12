import { csrfHeaders, durationLabel, durationOptions, minutesBetween, money, timeLabel } from './club';

export function calendarPage(config) {
    return {
        view: config.view || 'day',
        date: config.date,
        filters: { sport_id: '', court_id: '', booking_status: '', payment_status: '' },
        loading: true,
        data: { slots: [], courts: [], days: [] },
        sports: config.sports || [],
        courts: config.courts || [],
        drawer: null,
        edit: false,
        bookingOpen: false,
        holdOpen: false,
        conflict: null,
        memberQuery: '',
        memberHits: [],
        creatingMember: false,
        form: blankForm(),
        holdForm: blankHold(),
        newMember: { name: '', phone: '', member_number: '', email: '' },
        paying: { amount: '', payment_method: 'cash', proof: null, proofName: '' },
        proofModal: null,
        compact: Boolean(config.compact),
        fullReload: Boolean(config.fullReload),
        refreshTimer: null,
        layout: storedLayout(),
        durationOptions,

        get filteredCourts() {
            if (!this.filters.sport_id) return this.courts;
            return this.courts.filter((c) => String(c.sport_id) === String(this.filters.sport_id));
        },

        get dateLabel() {
            return this.data.label || this.pretty(this.date);
        },

        init() {
            this.load();
            this.refreshTimer = setInterval(() => {
                if (this.bookingOpen || this.holdOpen || this.conflict || this.proofModal) {
                    return;
                }
                if (this.fullReload) {
                    window.location.reload();
                    return;
                }
                this.load({ silent: true });
            }, 60000);
        },

        pretty(date) {
            return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
                weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
            });
        },

        shift(days) {
            const next = new Date(`${this.date}T00:00:00`);
            next.setDate(next.getDate() + days);
            this.date = next.toISOString().slice(0, 10);
            this.load();
        },

        today() {
            this.date = new Date().toISOString().slice(0, 10);
            this.load();
        },

        setView(view) {
            this.view = view;
            this.load();
        },

        setLayout(layout) {
            this.layout = layout;
            try {
                localStorage.setItem('calendar-layout', layout);
            } catch {
                // ignore storage errors
            }
        },

        async load(options = {}) {
            if (! options.silent) {
                this.loading = true;
            }
            const params = new URLSearchParams({ date: this.date, view: this.view, ...this.clean(this.filters) });
            const res = await fetch(`/api/calendar?${params}`, { headers: { Accept: 'application/json' } });
            this.data = await res.json();
            this.loading = false;
        },

        clean(obj) {
            return Object.fromEntries(Object.entries(obj).filter(([, v]) => v !== '' && v != null));
        },

        slotClass(cell) {
            if (cell.type === 'available') return 'slot-available';
            if (cell.type === 'block' || cell.type === 'closed') return 'slot-block';
            return `slot-${cell.booking?.tone || 'pending'}`;
        },

        slotIndex(slot) {
            return (this.data.slots || []).indexOf(slot);
        },

        slotRow(slot) {
            return this.slotIndex(slot) + 2;
        },

        courtCol(court) {
            return (this.data.courts || []).findIndex((item) => item.id === court.id) + 2;
        },

        showTimeLabel(slot) {
            if (!slot) return false;
            if (String(slot).endsWith(':00')) return true;
            return (this.data.courts || []).some((court) =>
                (court.cells || []).some((cell) => cell.type === 'booking' && cell.slot === slot)
            );
        },

        availableMinutes(cell, court) {
            const slotMins = this.data.slot_duration || 30;
            let mins = 0;
            for (const next of court.cells || []) {
                if (next.slot < cell.slot) continue;
                if (next.type !== 'available') break;
                mins += (next.span || 1) * slotMins;
            }
            return mins || slotMins;
        },

        openAvailable(cell, court) {
            if (cell.type !== 'available') return;
            const free = this.availableMinutes(cell, court);
            const duration = free >= 60 ? 60 : free;
            this.form = {
                ...blankForm(),
                booking_date: this.date,
                sport_id: court.sport.id,
                court_id: court.id,
                start_time: cell.slot,
                end_time: this.addMinutes(cell.slot, duration),
                total_amount: court.price_per_hour,
            };
            this.quote();
            this.bookingOpen = true;
            this.memberQuery = '';
            this.memberHits = [];
        },

        async openBooking(id) {
            const res = await fetch(`/bookings/${id}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.drawer = data.booking;
            this.drawer.payments = data.payments || [];
            this.edit = false;
            this.paying = {
                amount: this.drawer.remaining_amount || '',
                payment_method: this.drawer.payment_method || 'cash',
                proof: null,
                proofName: '',
            };
            this.proofModal = null;
        },

        startEdit() {
            const b = this.drawer;
            this.form = {
                member_id: b.member.id,
                member: b.member,
                sport_id: b.sport.id,
                court_id: b.court.id,
                booking_date: String(b.start_time).slice(0, 5) < '12:00' ? this.addDays(b.date, -1) : b.date,
                start_time: b.start_time,
                end_time: b.end_time,
                players_count: b.players_count,
                total_amount: b.total_amount,
                paid_amount: b.paid_amount,
                payment_method: b.payment_method || 'cash',
                notes: b.notes || '',
            };
            this.edit = true;
            this.quote();
        },

        currentDuration() {
            return minutesBetween(this.form.start_time, this.form.end_time);
        },

        formDurationLabel() {
            return durationLabel(this.form.start_time, this.form.end_time);
        },

        async quote() {
            if (!this.form.court_id || !this.form.start_time || !this.form.end_time) return;
            const params = new URLSearchParams({
                court_id: this.form.court_id,
                start_time: String(this.form.start_time).slice(0, 5),
                end_time: String(this.form.end_time).slice(0, 5),
            });
            const res = await fetch(`/bookings/quote/amount?${params}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.form.total_amount = data.amount;
            if (Number(this.form.paid_amount) > Number(this.form.total_amount)) {
                this.form.paid_amount = this.form.total_amount;
            }
        },

        remaining() {
            return Math.max(0, Number(this.form.total_amount || 0) - Number(this.form.paid_amount || 0));
        },

        setDuration(minutes) {
            this.form.end_time = this.addMinutes(this.form.start_time, Number(minutes));
            this.quote();
        },

        addDays(date, days) {
            const next = new Date(`${date}T00:00:00`);
            next.setDate(next.getDate() + Number(days));
            return `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`;
        },

        addMinutes(time, minutes) {
            const [h, m] = time.split(':').map(Number);
            const d = new Date();
            d.setHours(h, m + Number(minutes), 0, 0);
            return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        },

        async searchMembers() {
            if (this.memberQuery.trim().length < 2) {
                this.memberHits = [];
                return;
            }
            const res = await fetch(`/api/members/search?q=${encodeURIComponent(this.memberQuery)}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.memberHits = data.members || [];
        },

        pickMember(member) {
            this.form.member_id = member.id;
            this.form.member = member;
            this.memberHits = [];
            this.memberQuery = `${member.name} · ${member.member_number}`;
        },

        async saveMember() {
            const res = await fetch('/members', {
                ...csrfHeaders(),
                body: JSON.stringify(this.newMember),
            });
            const data = await res.json();
            if (!data.ok) {
                window.clubToast(data.message || 'Could not create member', 'err');
                return;
            }
            this.pickMember(data.member);
            this.creatingMember = false;
            this.newMember = { name: '', phone: '', member_number: '', email: '' };
            window.clubToast('Member created and selected');
        },

        async submitBooking() {
            const url = this.edit && this.drawer ? `/bookings/${this.drawer.id}` : '/bookings';
            const method = this.edit && this.drawer ? 'PUT' : 'POST';
            const res = await fetch(url, {
                ...csrfHeaders(method),
                body: JSON.stringify(this.form),
            });
            const data = await res.json();
            if (data.conflict) {
                this.conflict = data.booking;
                return;
            }
            if (!res.ok) {
                window.clubToast(data.message || 'Could not save booking', 'err');
                return;
            }
            this.bookingOpen = false;
            this.edit = false;
            this.drawer = data.booking;
            window.clubToast(data.message || 'Booking saved');
            this.load();
        },

        openHoldFromSlot(cell, court) {
            this.holdForm = {
                ...blankHold(),
                booking_date: this.date,
                sport_id: court.sport.id,
                court_id: court.id,
                start_time: cell.slot,
                end_time: this.addMinutes(cell.slot, 60),
                expires_at: `${this.date}T${cell.slot}`,
            };
            this.form.member = null;
            this.form.member_id = '';
            this.holdOpen = true;
        },

        openHoldFromForm() {
            this.holdForm = {
                ...blankHold(),
                booking_date: this.form.booking_date,
                sport_id: this.form.sport_id,
                court_id: this.form.court_id,
                start_time: this.form.start_time,
                end_time: this.form.end_time,
                expires_at: `${this.form.booking_date}T${this.form.start_time}`,
            };
            this.bookingOpen = false;
            this.holdOpen = true;
        },

        async submitHold() {
            const res = await fetch('/bookings/hold', {
                ...csrfHeaders(),
                body: JSON.stringify({ ...this.holdForm, member_id: this.form.member_id }),
            });
            const data = await res.json();
            if (data.conflict) {
                this.conflict = data.booking;
                return;
            }
            if (!res.ok) {
                window.clubToast(data.message || 'Could not place hold', 'err');
                return;
            }
            this.holdOpen = false;
            window.clubToast(data.message);
            this.load();
        },

        async cancelBooking() {
            const ok = await window.clubConfirm({
                title: 'Cancel booking?',
                message: 'Are you sure you want to cancel this booking?',
                extra: 'Cancelled bookings remain in historical reports.',
            });
            if (!ok) return;
            const reason = prompt('Cancellation reason (optional)') || '';
            const res = await fetch(`/bookings/${this.drawer.id}/cancel`, {
                ...csrfHeaders(),
                body: JSON.stringify({ reason }),
            });
            const data = await res.json();
            window.clubToast(data.message || 'Cancelled');
            this.drawer = data.booking;
            this.load();
        },

        async releaseHold() {
            const ok = await window.clubConfirm({
                title: 'Release hold?',
                message: 'This slot will become available again.',
                confirmText: 'Release hold',
                tone: 'primary',
            });
            if (!ok) return;
            const res = await fetch(`/bookings/${this.drawer.id}/release`, { ...csrfHeaders() });
            const data = await res.json();
            window.clubToast(data.message);
            this.drawer = null;
            this.load();
        },

        setProof(event) {
            const file = event.target.files?.[0] || null;
            this.paying.proof = file;
            this.paying.proofName = file ? file.name : '';
        },

        async takePayment() {
            const body = new FormData();
            body.append('amount', this.paying.amount);
            body.append('payment_method', this.paying.payment_method || 'cash');
            if (this.paying.proof) {
                body.append('proof', this.paying.proof);
            }

            const res = await fetch(`/bookings/${this.drawer.id}/payments`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': window.Club.csrf,
                },
                body,
            });
            const data = await res.json();
            if (!res.ok) {
                window.clubToast(data.message || 'Payment failed', 'err');
                return;
            }
            this.drawer = data.booking;
            this.drawer.payments = data.payments || [];
            this.paying = { amount: this.drawer.remaining_amount || '', payment_method: this.drawer.payment_method || 'cash', proof: null, proofName: '' };
            window.clubToast(data.message);
            this.load();
        },

        async deletePayment(pay) {
            const ok = await window.clubConfirm({
                title: 'Delete payment?',
                message: 'This transaction will be removed and the remaining amount will update.',
                confirmText: 'Delete',
            });
            if (!ok) return;

            const res = await fetch(`/bookings/${this.drawer.id}/payments/${pay.id}`, {
                ...csrfHeaders('DELETE'),
            });
            const data = await res.json();
            if (!res.ok) {
                window.clubToast(data.message || 'Could not delete payment', 'err');
                return;
            }
            this.drawer = data.booking;
            this.drawer.payments = data.payments || [];
            this.paying.amount = this.drawer.remaining_amount || '';
            if (this.proofModal === pay.proof_url) {
                this.proofModal = null;
            }
            window.clubToast(data.message);
            this.load();
        },

        money,
        timeLabel,
        durationLabel,
    };
}

function storedLayout() {
    try {
        return localStorage.getItem('calendar-layout') === 'horizontal' ? 'horizontal' : 'vertical';
    } catch {
        return 'vertical';
    }
}

function blankForm() {
    return {
        member_id: '',
        member: null,
        sport_id: '',
        court_id: '',
        booking_date: '',
        start_time: '',
        end_time: '',
        players_count: 2,
        total_amount: 0,
        paid_amount: 0,
        payment_method: 'cash',
        notes: '',
    };
}

function blankHold() {
    return {
        sport_id: '',
        court_id: '',
        booking_date: '',
        start_time: '',
        end_time: '',
        reason: 'Waiting for member confirmation',
        expires_at: '',
        notes: '',
    };
}
