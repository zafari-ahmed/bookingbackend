<header class="no-print sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200/70 bg-white/80 px-4 py-3 backdrop-blur sm:px-6">
    <button type="button" class="btn btn-ghost px-3" title="Menu" @click="window.innerWidth >= 1024 ? toggleSidebar() : mobileNav = !mobileNav">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
        </svg>
    </button>
    <div class="relative min-w-0 flex-1">
        <input class="input pl-10" placeholder="Search member, phone, booking ID, court…" x-model="search" @input.debounce.250ms="lookup()">
        <svg class="absolute left-3 top-3 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
        <div x-show="openSearch" x-cloak @click.away="openSearch=false" class="absolute mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <template x-if="searching"><div class="p-4 text-sm text-slate-500">Searching…</div></template>
            <template x-for="member in results.members" :key="'m'+member.id">
                <a :href="'/members/'+member.id" class="block px-4 py-3 hover:bg-mist">
                    <div class="font-semibold" x-text="member.name"></div>
                    <div class="text-xs text-slate-500" x-text="member.member_number + ' · ' + member.phone"></div>
                </a>
            </template>
            <template x-for="booking in results.bookings" :key="'b'+booking.id">
                <a :href="'/calendar?date='+booking.date" class="block px-4 py-3 hover:bg-mist">
                    <div class="font-semibold" x-text="booking.booking_number"></div>
                    <div class="text-xs text-slate-500" x-text="booking.member + ' · ' + booking.court"></div>
                </a>
            </template>
            <template x-for="court in results.courts" :key="'c'+court.id">
                <a href="{{ route('calendar.index') }}" class="block px-4 py-3 text-sm hover:bg-mist" x-text="court.name"></a>
            </template>
        </div>
    </div>
    <div class="hidden rounded-2xl bg-mist px-3 py-2 text-sm font-semibold text-navy md:block">{{ now()->format('D, d M Y') }}</div>
    <div class="relative">
        <button class="btn btn-ghost relative" @click="openNotes=!openNotes; pingNotifications()">
            Alerts
            <span class="absolute -right-1 -top-1 rounded-full bg-red-500 px-1.5 text-[10px] text-white" x-show="unread>0" x-text="unread"></span>
        </button>
        <div x-show="openNotes" x-cloak @click.away="openNotes=false" class="absolute right-0 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl">
            <div class="flex items-center justify-between px-2 py-1">
                <div class="text-sm font-bold">Notifications</div>
                <button class="text-xs text-electric" @click="markAllRead()">Mark all read</button>
            </div>
            <template x-if="!notifications.length"><div class="p-4 text-sm text-slate-500">No notifications yet.</div></template>
            <template x-for="item in notifications" :key="item.id">
                <div class="rounded-xl px-3 py-2 hover:bg-mist">
                    <div class="text-sm font-semibold" x-text="item.title"></div>
                    <div class="text-xs text-slate-500" x-text="item.message"></div>
                </div>
            </template>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <div class="hidden text-right sm:block">
            <div class="text-sm font-bold">{{ auth()->user()->name }}</div>
            <div class="text-xs capitalize text-slate-500">{{ auth()->user()->role?->label() }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-soft">Logout</button>
        </form>
    </div>
</header>
