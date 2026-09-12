<div class="mt-4 grid gap-3 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-semibold">Member</label>
        <input class="input" placeholder="Search by name, phone or member number" x-model="memberQuery" @input.debounce.250ms="searchMembers()">
        @include('bookings.partials.member-results')
        <template x-if="form.member">
            <div class="mt-2 rounded-2xl bg-green-50 p-3 text-sm">
                <div class="font-bold" x-text="form.member.name"></div>
                <div class="text-slate-600" x-text="(form.member.member_number||'')+' · '+(form.member.phone||'')"></div>
            </div>
        </template>
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Date</label>
        <input class="input" type="date" x-model="form.booking_date">
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Sport</label>
        <select class="select" x-model="form.sport_id">
            <option value="">Select sport</option>
            @foreach ($sports as $sport)<option value="{{ $sport->id }}">{{ $sport->name }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Court</label>
        <select class="select" x-model="form.court_id" @change="quote()">
            <option value="">Select court</option>
            <template x-for="court in (filteredCourts || courts || [])" :key="court.id">
                <option :value="court.id" x-show="!form.sport_id || String(court.sport_id||court.sport?.id)===String(form.sport_id)" x-text="court.name"></option>
            </template>
        </select>
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Start</label>
        <input class="input" type="time" step="1800" x-model="form.start_time" @change="quote()">
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">End</label>
        <input class="input" type="time" step="1800" x-model="form.end_time" @change="quote()">
    </div>
    <div class="sm:col-span-2">
        <div class="mb-2 text-sm text-slate-500" x-show="formDurationLabel()">
            Duration <b class="text-navy" x-text="formDurationLabel()"></b>
            · price updates automatically
        </div>
        <div class="flex flex-wrap gap-2">
            <template x-for="opt in durationOptions" :key="opt.mins">
                <button type="button" class="btn" :class="currentDuration()===opt.mins ? 'btn-primary' : 'btn-ghost'" @click="setDuration(opt.mins)" x-text="opt.label"></button>
            </template>
        </div>
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Players</label>
        <input class="input" type="number" min="1" x-model="form.players_count">
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Total amount</label>
        <input class="input" type="number" min="0" x-model="form.total_amount">
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Paid amount</label>
        <input class="input" type="number" min="0" x-model="form.paid_amount">
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Remaining</label>
        <div class="input bg-mist font-bold" x-text="money(remaining())"></div>
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold">Payment method</label>
        <select class="select" x-model="form.payment_method">
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bank">Bank transfer</option>
            <option value="online">Online</option>
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-semibold">Internal notes</label>
        <textarea class="input" rows="3" x-model="form.notes"></textarea>
    </div>
</div>
