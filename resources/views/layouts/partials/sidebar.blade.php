<aside class="sidebar no-print fixed inset-y-0 left-0 z-40 -translate-x-full overflow-y-auto px-3 py-5 text-white transition-all duration-200 lg:static lg:translate-x-0"
     :class="{
        '!translate-x-0 shadow-2xl': mobileNav,
        'is-collapsed': sidebarCollapsed
     }">
    <div class="mb-8 flex items-center gap-3 px-2">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-electric text-lg font-black">SA</div>
        <div class="sidebar-label min-w-0">
            <div class="font-display text-lg font-extrabold leading-tight">Sport Avenue</div>
            <div class="text-xs text-white/50">Club operations</div>
        </div>
    </div>
    <nav class="space-y-1">
        @php
            $items = [
                ['dashboard', 'Dashboard', 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['calendar.index', 'Booking Calendar', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['bookings.create', 'New Booking', 'M12 4v16m8-8H4'],
                ['members.index', 'Members', 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['sports.index', 'Sports & Courts', 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
            ];
        @endphp
        @foreach ($items as [$route, $label, $icon])
            <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs(explode('.', $route)[0].'*') ? 'is-active' : '' }}" title="{{ $label }}">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                <span class="sidebar-label">{{ $label }}</span>
            </a>
        @endforeach
        @if (auth()->user()->canViewReports())
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" title="Reports">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="sidebar-label">Reports</span>
            </a>
        @endif
        @if (auth()->user()->canManageSettings())
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'is-active' : '' }}" title="Settings">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="sidebar-label">Settings</span>
            </a>
        @endif
    </nav>
    <div class="sidebar-label mt-10 rounded-2xl bg-white/5 p-4 text-sm text-white/70">
        <div class="font-semibold text-white">Staff shift</div>
        <div class="mt-1">{{ now()->format('D, d M Y') }}</div>
        <div class="mt-2 text-xs uppercase tracking-wide text-lime">{{ auth()->user()->role?->label() }} access</div>
    </div>
</aside>
<div class="fixed inset-0 z-30 bg-navy/40 lg:hidden" x-show="mobileNav" x-cloak @click="mobileNav=false"></div>
