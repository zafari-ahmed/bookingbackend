<div x-show="drawer" x-cloak class="fixed inset-0 z-40 flex justify-end bg-navy/40" @click.self="drawer=null">
    <aside class="drawer h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-electric">Booking details</div>
                <h2 class="font-display text-2xl font-extrabold text-navy" x-text="drawer?.booking_number"></h2>
            </div>
            <button class="btn btn-ghost" @click="drawer=null">Close</button>
        </div>
        <template x-if="drawer">
            <div class="mt-5 space-y-5">
                <span class="badge" :class="'badge-'+drawer.tone" x-text="drawer.label"></span>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><div class="text-slate-400">Date</div><div class="font-semibold" x-text="drawer.date"></div></div>
                    <div><div class="text-slate-400">Time</div><div class="font-semibold" x-text="timeLabel(drawer.start_time)+' – '+timeLabel(drawer.end_time)"></div></div>
                    <div><div class="text-slate-400">Duration</div><div class="font-semibold" x-text="durationLabel(drawer.start_time, drawer.end_time)"></div></div>
                    <div><div class="text-slate-400">Sport</div><div class="font-semibold" x-text="drawer.sport?.name"></div></div>
                    <div><div class="text-slate-400">Court</div><div class="font-semibold" x-text="drawer.court?.name"></div></div>
                    <div><div class="text-slate-400">Member</div><div class="font-semibold" x-text="drawer.member?.name"></div></div>
                    <div><div class="text-slate-400">Member no.</div><div class="font-semibold" x-text="drawer.member?.member_number"></div></div>
                    <div><div class="text-slate-400">Players</div><div class="font-semibold" x-text="drawer.players_count"></div></div>
                    <div><div class="text-slate-400">Created by</div><div class="font-semibold" x-text="drawer.created_by || '—'"></div></div>
                </div>
                <div class="rounded-2xl bg-mist p-4">
                    <div class="font-bold">Payment</div>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-sm">
                        <div>Total<br><b x-text="money(drawer.total_amount)"></b></div>
                        <div>Paid<br><b x-text="money(drawer.paid_amount)"></b></div>
                        <div>Remaining<br><b x-text="money(drawer.remaining_amount)"></b></div>
                    </div>
                    <div class="mt-2 text-sm text-slate-500" x-text="(drawer.payment_method || '—') + (drawer.payments?.[0]?.payment_date ? ' · '+drawer.payments[0].payment_date : '')"></div>
                    <div class="mt-3 space-y-2" x-show="drawer.payments?.length">
                        <template x-for="pay in drawer.payments" :key="pay.id">
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2 text-sm">
                                <div class="min-w-0">
                                    <div class="font-semibold" x-text="money(pay.amount)+' · '+(pay.payment_method || '')"></div>
                                    <div class="text-xs text-slate-400" x-text="pay.payment_date"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" x-show="pay.proof_url" class="shrink-0" @click="proofModal = pay.proof_url">
                                        <img :src="pay.proof_url" alt="Payment proof" class="h-12 w-12 rounded-lg object-cover">
                                    </button>
                                    <button type="button" class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Delete payment" @click="deletePayment(pay)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="rounded-2xl bg-mist p-4 text-sm" x-show="drawer.notes">
                    <div class="font-bold">Internal notes</div>
                    <div class="mt-1 whitespace-pre-wrap" x-text="drawer.notes"></div>
                </div>
                <div class="rounded-2xl bg-violet-50 p-4 text-sm" x-show="drawer.hold">
                    <div class="font-bold text-hold">On hold</div>
                    <div x-text="drawer.hold?.reason"></div>
                    <div class="text-slate-500" x-text="'Hold until '+drawer.hold?.expires_at"></div>
                </div>
                <div class="rounded-2xl bg-red-50 p-4 text-sm" x-show="drawer.booking_status==='cancelled'">
                    <div class="font-bold text-red-700">Cancelled</div>
                    <div x-text="drawer.cancellation_reason || 'No reason given'"></div>
                    <div class="text-slate-500" x-text="(drawer.cancelled_by||'')+' · '+(drawer.cancelled_at||'')"></div>
                </div>

                <div class="grid gap-2" x-show="drawer.booking_status!=='cancelled' && drawer.remaining_amount>0">
                    <div class="font-bold">Record payment</div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Amount</label>
                            <input class="input" type="number" min="1" x-model="paying.amount">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Payment method</label>
                            <select class="select" x-model="paying.payment_method">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Payment proof</label>
                            <input class="input" type="file" accept="image/*" @change="setProof($event)">
                            <div class="mt-1 text-xs text-slate-400" x-show="paying.proofName" x-text="paying.proofName"></div>
                        </div>
                    </div>
                    <button class="btn btn-primary" @click="takePayment()">Record payment</button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button class="btn btn-primary" x-show="drawer.booking_status!=='cancelled'" @click="startEdit(); bookingOpen=true">Edit booking</button>
                    <button class="btn btn-soft" x-show="drawer.booking_status==='on_hold'" @click="releaseHold()">Release hold</button>
                    <button class="btn btn-danger" x-show="drawer.booking_status!=='cancelled' && window.Club.user.canCancel" @click="cancelBooking()">Cancel booking</button>
                    <a class="btn btn-ghost" :href="'/members/'+drawer.member.id">Member profile</a>
                </div>
            </div>
        </template>
    </aside>
</div>
<div x-show="proofModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-navy/70 p-4" @click.self="proofModal=null">
    <div class="modal-sheet relative max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-3xl bg-white p-3 shadow-2xl">
        <button class="btn btn-ghost absolute right-3 top-3" @click="proofModal=null">Close</button>
        <img :src="proofModal" alt="Payment proof" class="max-h-[80vh] w-full rounded-2xl object-contain">
    </div>
</div>
