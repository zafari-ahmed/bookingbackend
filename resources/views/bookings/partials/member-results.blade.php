<div class="mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white" x-show="memberHits.length">
    <template x-for="member in memberHits" :key="member.id">
        <button type="button" class="block w-full px-3 py-2 text-left hover:bg-mist" @click="pickMember(member)">
            <div class="font-semibold" x-text="member.name"></div>
            <div class="text-xs text-slate-500" x-text="member.member_number+' · '+member.phone+(member.total_bookings!=null ? ' · '+member.total_bookings+' bookings' : '')"></div>
        </button>
    </template>
</div>
<div class="mt-2 text-sm" x-show="memberQuery.length>=2 && !memberHits.length && !form.member">
    Member not found.
    <button type="button" class="font-bold text-electric" @click="creatingMember=true; newMember.phone=memberQuery">+ Create new member</button>
</div>
<div class="mt-3 grid gap-2 rounded-2xl bg-mist p-3" x-show="creatingMember">
    <input class="input" placeholder="Name" x-model="newMember.name">
    <input class="input" placeholder="Phone" x-model="newMember.phone">
    <input class="input" placeholder="Member number (optional)" x-model="newMember.member_number">
    <input class="input" placeholder="Email" x-model="newMember.email">
    <button type="button" class="btn btn-primary" @click="saveMember()">Save member</button>
</div>
