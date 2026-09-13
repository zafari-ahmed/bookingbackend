import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
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
        fc: null,
        durationOptions,

        get filteredCourts() {
            if (!this.filters.sport_id) return this.courts;
            return this.courts.filter((c) => String(c.sport_id) === String(this.filters.sport_id));
        },

        get dateLabel() {
            return this.pretty(this.date);
        },

        init() {
            this.$nextTick(() => this.mountCalendar());
            this.refreshTimer = setInterval(() => {
                if (this.bookingOpen || this.holdOpen || this.conflict || this.proofModal) {
                    return;
                }
                this.fc?.refetchEvents();
            }, 60000);
        },

        pretty(date) {
            return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
                weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
            });
        },

        shift(days) {
            this.fc?.incrementDate({ days });
        },

        today() {
            this.fc?.today();
        },

        setView(view) {
            this.view = view;
            const map = { day: 'timeGridDay', week: 'timeGridWeek', month: 'dayGridMonth' };
            this.fc?.changeView(map[view] || 'timeGridDay');
        },

        async load() {
            this.fc?.refetchEvents();
        },

        mountCalendar() {
            if (! this.$refs.fc || this.fc) return;

            const clock = { hour: '2-digit', minute: '2-digit', hour12: false };

            this.fc = new Calendar(this.$refs.fc, {
                plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
                initialView: this.view === 'month' ? 'dayGridMonth' : (this.view === 'week' ? 'timeGridWeek' : 'timeGridDay'),
                initialDate: this.date,
                timeZone: 'local',
                height: this.compact ? 720 : 'auto',
                contentHeight: this.compact ? 640 : 'auto',
                expandRows: false,
                nowIndicator: true,
                allDaySlot: false,
                slotDuration: '00:30:00',
                snapDuration: '00:30:00',
                slotMinTime: '00:00:00',
                slotMaxTime: '24:00:00',
                scrollTime: '00:00:00',
                slotLabelInterval: '01:00:00',
                slotLabelFormat: clock,
                eventTimeFormat: clock,
                views: {
                    timeGridDay: {
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
                        dayHeaderFormat: { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' },
                    },
                    timeGridWeek: {
                        slotMinTime: '00:00:00',
                        slotMaxTime: '24:00:00',
                    },
                },
                selectable: true,
                selectMirror: true,
                dayMaxEvents: false,
                headerToolbar: this.compact
                    ? { left: 'prev,next today', center: 'title', right: '' }
                    : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
                buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day', list: 'List' },
                events: (info, success, failure) => this.fetchEvents(info, success, failure),
                datesSet: (info) => {
                    const current = info.view.currentStart || info.start;
                    this.date = this.fmtDate(info.view.type === 'timeGridDay' ? info.start : current);
                    this.view = info.view.type.includes('Month') ? 'month' : (info.view.type.includes('Week') || info.view.type.includes('list') ? 'week' : 'day');
                    this.$nextTick(() => this.paintMonthCounts());
                },
                eventsSet: () => {
                    this.$nextTick(() => this.paintMonthCounts());
                },
                dateClick: (info) => {
                    if (info.view.type === 'dayGridMonth') {
                        this.openDay(info.dateStr || info.date);
                    }
                },
                eventClick: (info) => {
                    info.jsEvent.preventDefault();
                    this.openBooking(info.event.id);
                },
                select: (info) => {
                    this.fc.unselect();
                    if (info.view.type === 'dayGridMonth') {
                        this.openDay(info.startStr || info.start);
                        return;
                    }
                    this.openFromCalendar(info.start, info.end);
                },
                eventContent: (arg) => this.eventHtml(arg),
            });

            this.fc.render();
        },

        async fetchEvents(info, success, failure) {
            try {
                const params = new URLSearchParams({
                    from: info.startStr.slice(0, 10),
                    to: info.endStr.slice(0, 10),
                    ...this.clean(this.filters),
                });
                const res = await fetch(`/api/calendar/events?${params}`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                const events = Array.isArray(data.events) ? data.events : Object.values(data.events || {});
                success(events);
                this.loading = false;
            } catch (error) {
                this.loading = false;
                failure(error);
            }
        },

        paintMonthCounts() {
            const root = this.$refs.fc;
            if (! root || this.fc?.view?.type !== 'dayGridMonth') {
                return;
            }

            const counts = {};
            for (const event of this.fc.getEvents()) {
                const day = this.eventDay(event);
                if (! day) continue;
                counts[day] = (counts[day] || 0) + 1;
            }

            root.querySelectorAll('.fc-daygrid-day').forEach((cell) => {
                const day = cell.dataset.date;
                const frame = cell.querySelector('.fc-daygrid-day-frame');
                if (! day || ! frame) return;

                let badge = frame.querySelector('.fc-club-day-count');
                if (! badge) {
                    badge = document.createElement('div');
                    badge.className = 'fc-club-day-count';
                    frame.appendChild(badge);
                }

                const total = counts[day] || 0;
                badge.hidden = total === 0;
                badge.textContent = total === 1 ? '1 booking' : `${total} bookings`;
            });
        },

        eventDay(event) {
            const start = event.start || event.startStr;
            if (start) {
                const date = start instanceof Date ? start : new Date(start);
                if (! Number.isNaN(date.getTime())) {
                    return this.fmtDate(date);
                }
            }

            const booking = event.extendedProps || {};
            if (booking.date) {
                return String(booking.date).slice(0, 10);
            }

            return String(event.start || '').slice(0, 10);
        },

        openDay(value) {
            const day = typeof value === 'string' && value.length >= 10 ? value.slice(0, 10) : this.fmtDate(value);
            this.date = day;
            this.view = 'day';
            if (! this.fc) return;
            this.fc.changeView('timeGridDay', day);
            this.fc.refetchEvents();
        },

        eventHtml(arg) {
            const booking = arg.event.extendedProps || {};
            const name = this.escapeHtml(booking.member?.name || arg.event.title);
            const court = this.escapeHtml(booking.court?.name || '');
            const label = this.escapeHtml(booking.label || '');
            const amount = booking.total_amount != null ? this.escapeHtml(money(booking.total_amount)) : '';

            return {
                html: `<div class="fc-club-event">
                    <div class="fc-club-time">${this.escapeHtml(arg.timeText)}</div>
                    <div class="fc-club-name">${name}</div>
                    <div class="fc-club-meta">${court}${label ? ' · '+label : ''}</div>
                    ${amount ? `<div class="fc-club-meta">${amount}</div>` : ''}
                </div>`,
            };
        },

        openFromCalendar(startDate, endDate) {
            const court = this.filteredCourts.find((item) => String(item.id) === String(this.filters.court_id)) || this.filteredCourts[0];
            if (! court) {
                window.clubToast('Select a court first', 'err');
                return;
            }

            const start = this.hhmm(startDate);
            let end = this.hhmm(endDate);
            if (! end || end === start) {
                end = this.addMinutes(start, 60);
            }

            const clock = this.fmtDate(startDate);
            const session = start < '12:00' ? this.addDays(clock, -1) : clock;

            this.form = {
                ...blankForm(),
                booking_date: session,
                sport_id: court.sport_id || court.sport?.id,
                court_id: court.id,
                start_time: start,
                end_time: end,
                total_amount: court.price_per_hour,
            };
            this.quote();
            this.bookingOpen = true;
            this.memberQuery = '';
            this.memberHits = [];
        },

        fmtDate(value) {
            const date = value instanceof Date ? value : new Date(value);
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        },

        hhmm(value) {
            const date = value instanceof Date ? value : new Date(value);
            return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
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
