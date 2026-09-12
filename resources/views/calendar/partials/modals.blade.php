<div x-show="bookingOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-navy/40 p-4 sm:items-center">
    <div class="modal-sheet card max-h-[92vh] w-full max-w-2xl overflow-y-auto p-6">
        <div class="flex items-center justify-between">
            <h3 class="font-display text-2xl font-extrabold" x-text="edit ? 'Edit booking' : 'Create new booking'"></h3>
            <button class="btn btn-ghost" @click="bookingOpen=false">Close</button>
        </div>
        @include('bookings.partials.fields')
        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button class="btn btn-ghost" @click="bookingOpen=false">Cancel</button>
            <button class="btn btn-soft" x-show="window.Club.user.canHold && !edit" @click="openHoldFromForm()">Place on hold</button>
            <button class="btn btn-primary" @click="submitBooking()">Confirm booking</button>
        </div>
    </div>
</div>

<div x-show="holdOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-navy/40 p-4">
    <div class="modal-sheet card w-full max-w-lg p-6">
        <h3 class="font-display text-2xl font-extrabold">Hold this slot</h3>
        <div class="mt-4 space-y-3">
            <input class="input" placeholder="Search member" x-model="memberQuery" @input.debounce.250ms="searchMembers()">
            @include('bookings.partials.member-results')
            <input class="input" placeholder="Reason" x-model="holdForm.reason">
            <input class="input" type="datetime-local" x-model="holdForm.expires_at">
            <textarea class="input" rows="3" placeholder="Notes" x-model="holdForm.notes"></textarea>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button class="btn btn-ghost" @click="holdOpen=false">Close</button>
            <button class="btn btn-primary" @click="submitHold()">Place hold</button>
        </div>
    </div>
</div>

<div x-show="conflict" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-navy/40 p-4">
    <div class="modal-sheet card w-full max-w-lg p-6">
        <h3 class="font-display text-2xl font-extrabold">Slot unavailable</h3>
        <p class="mt-2 text-slate-600">This court is already booked for the selected time.</p>
        <template x-if="conflict">
            <div class="mt-4 rounded-2xl bg-mist p-4">
                <div class="font-bold" x-text="conflict.court?.name"></div>
                <div x-text="timeLabel(conflict.start_time)+' – '+timeLabel(conflict.end_time)"></div>
                <div x-text="conflict.member?.name"></div>
                <div class="badge mt-2" :class="'badge-'+conflict.tone" x-text="conflict.label"></div>
            </div>
        </template>
        <div class="mt-4 flex justify-end gap-2">
            <button class="btn btn-ghost" @click="conflict=null">Choose another slot</button>
            <button class="btn btn-primary" @click="openBooking(conflict.id); conflict=null">View booking</button>
        </div>
    </div>
</div>
