import { csrfHeaders, durationLabel, durationOptions, minutesBetween, money } from './club';

export function bookingForm(config) {
    return {
        sports: config.sports || [],
        form: {
            member_id: '',
            member: null,
            sport_id: config.prefill?.sport_id || '',
            court_id: config.prefill?.court_id || '',
            booking_date: config.prefill?.date || new Date().toISOString().slice(0, 10),
            start_time: config.prefill?.start_time || '18:00',
            end_time: '19:00',
            players_count: 2,
            total_amount: 0,
            paid_amount: 0,
            payment_method: 'cash',
            notes: '',
        },
        memberQuery: '',
        memberHits: [],
        creatingMember: false,
        newMember: { name: '', phone: '', member_number: '', email: '' },
        conflict: null,
        saving: false,
        durationOptions,

        get courts() {
            const sport = this.sports.find((s) => String(s.id) === String(this.form.sport_id));
            return sport?.courts || [];
        },

        remaining() {
            return Math.max(0, Number(this.form.total_amount || 0) - Number(this.form.paid_amount || 0));
        },

        currentDuration() {
            return minutesBetween(this.form.start_time, this.form.end_time);
        },

        formDurationLabel() {
            return durationLabel(this.form.start_time, this.form.end_time);
        },

        async init() {
            if (this.form.court_id) await this.quote();
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
        },

        setDuration(minutes) {
            const [h, m] = this.form.start_time.split(':').map(Number);
            const d = new Date();
            d.setHours(h, m + Number(minutes), 0, 0);
            this.form.end_time = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
            this.quote();
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
            const res = await fetch('/members', { ...csrfHeaders(), body: JSON.stringify(this.newMember) });
            const data = await res.json();
            if (!data.ok) return window.clubToast(data.message || 'Could not create member', 'err');
            this.pickMember(data.member);
            this.creatingMember = false;
            window.clubToast('Member created and selected');
        },

        async submit() {
            this.saving = true;
            const res = await fetch('/bookings', { ...csrfHeaders(), body: JSON.stringify(this.form) });
            const data = await res.json();
            this.saving = false;
            if (data.conflict) {
                this.conflict = data.booking;
                return;
            }
            if (!res.ok) return window.clubToast(data.message || 'Could not create booking', 'err');
            window.location = `/calendar?date=${this.form.booking_date}`;
        },

        money,
    };
}
