@props(['drawer' => false])

@php
    $user = auth()->user();

    $items = collect([
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dashboard', 'href' => route('dashboard')],
        ['route' => 'cases.index', 'label' => 'All Cases', 'icon' => 'cases', 'href' => route('cases.index')],
        ['route' => 'cases.create', 'label' => 'New Case', 'icon' => 'new-case', 'href' => route('cases.create')],
        ['route' => 'cases.index', 'label' => 'High Priority', 'icon' => 'priority', 'href' => route('cases.index', ['high_only' => 1]), 'active' => request()->routeIs('cases.index') && request()->boolean('high_only')],
        ['route' => 'notifications.index', 'label' => 'Notifications', 'icon' => 'notifications', 'href' => route('notifications.index')],
        ['route' => 'activity.index', 'label' => 'My Activity', 'icon' => 'activity', 'href' => route('activity.index')],
        ['route' => 'profile.*', 'label' => 'My Profile', 'icon' => 'user', 'href' => route('profile.show')],
        ['route' => 'users.*', 'label' => 'Users', 'icon' => 'users', 'href' => route('users.index'), 'gate' => 'manage-all-users'],
        ['route' => 'departments.*', 'label' => 'Departments & Access', 'icon' => 'departments', 'href' => route('departments.index'), 'gate' => 'manage-departments'],
    ])->filter(fn (array $item): bool => ! isset($item['gate']) || Gate::allows($item['gate']));

    $isActive = function (array $item): bool {
        if (array_key_exists('active', $item)) {
            return $item['active'];
        }

        if ($item['route'] === 'cases.index') {
            return request()->routeIs('cases.index') && ! request()->boolean('high_only');
        }

        return request()->routeIs($item['route']);
    };
@endphp

<nav
    x-data
    @class([
        'flex flex-col bg-navy',
        'h-full w-full' => $drawer,
        'sticky top-0 hidden h-screen w-sidebar shrink-0 transition-[width] duration-200 md:flex rail:w-rail' => ! $drawer,
    ])
    aria-label="Main navigation"
>
    <div @class([
        'flex items-center gap-3 px-3 py-4',
        'justify-between' => $drawer,
    ])>
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-teal text-sm font-extrabold tracking-wider text-text-inverse">
                DC
            </span>
            <span @class(['min-w-0', 'rail:hidden' => ! $drawer])>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-text-inverse-muted">
                    Government of Sindh
                </span>
                <span class="block truncate text-sm font-bold text-text-inverse">District Coordination</span>
            </span>
        </a>

        @if ($drawer)
            <button type="button" @click="$store.sidebar.closeDrawer()"
                    class="flex size-9 items-center justify-center rounded-control bg-navy-hover text-text-inverse-muted hover:text-text-inverse"
                    aria-label="Close navigation">
                <x-icon name="close" class="size-4" />
            </button>
        @endif
    </div>

    @unless ($drawer)
        <div class="flex justify-end px-3 pb-1 rail:justify-center">
            <button type="button" @click="$store.sidebar.toggle()"
                    class="flex size-8 items-center justify-center rounded-[9px] bg-navy-hover text-text-inverse-muted transition hover:bg-navy-light hover:text-text-inverse">
                <x-icon name="chevron-left" class="size-4 rail:hidden" />
                <x-icon name="chevron-right" class="hidden size-4 rail:block" />
                {{-- The accessible name follows the same CSS state, so it stays
                     correct without a script having to rewrite an aria-label. --}}
                <span class="sr-only rail:hidden">Collapse navigation</span>
                <span class="sr-only hidden rail:inline">Expand navigation</span>
            </button>
        </div>
    @endunless

    <ul class="grid gap-1 px-2.5 py-1.5">
        @foreach ($items as $item)
            @php $active = $isActive($item); @endphp
            <li class="relative group">
                <a href="{{ $item['href'] }}"
                   @class([
                       'relative flex min-h-11 items-center gap-3 overflow-hidden rounded-control px-3 text-sm transition',
                       'justify-start rail:justify-center' => ! $drawer,
                       'bg-navy-light font-bold text-text-inverse' => $active,
                       'font-medium text-text-inverse-muted hover:bg-navy-hover hover:text-text-inverse' => ! $active,
                   ])
                   @if ($active) aria-current="page" @endif>
                    {{-- Active nav item: navy-light pill + teal left accent bar. --}}
                    <span @class([
                        'absolute inset-y-2 left-0 w-[3px] rounded-r-[3px]',
                        'bg-teal' => $active,
                        'bg-transparent' => ! $active,
                    ])></span>
                    <x-icon :name="$item['icon']" class="size-[18px] shrink-0" />
                    <span @class(['whitespace-nowrap', 'rail:hidden' => ! $drawer])>
                        {{ $item['label'] }}
                    </span>
                </a>

                @unless ($drawer)
                    {{-- Collapsed rail shows the label as a hover tooltip. --}}
                    <span class="pointer-events-none absolute left-full top-1/2 z-50 ml-2.5 hidden -translate-y-1/2 whitespace-nowrap rounded-[9px] bg-navy-light px-3 py-1.5 text-xs font-semibold text-text-inverse rail:group-hover:block">
                        {{ $item['label'] }}
                    </span>
                @endunless
            </li>
        @endforeach
    </ul>

    <div class="mt-auto px-2.5 pb-4 pt-3">
        <div @class([
            'flex items-center gap-3 border-t border-navy-light pt-3',
            'justify-start rail:justify-center' => ! $drawer,
        ])>
            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-teal text-xs font-bold text-text-inverse">
                {{ $user->initials() }}
            </span>
            <a href="{{ route('profile.show') }}" @class(['min-w-0 flex-1', 'rail:hidden' => ! $drawer])>
                <p class="truncate text-[13px] font-bold text-text-inverse">{{ $user->name }}</p>
                <p class="truncate text-[11px] text-text-inverse-muted">{{ $user->contextLabel() }}</p>
            </a>
            <form method="POST" action="{{ route('logout') }}" @class(['rail:hidden' => ! $drawer])>
                @csrf
                <button type="submit"
                        class="flex size-9 items-center justify-center rounded-lg text-text-inverse-muted transition hover:bg-navy-light hover:text-text-inverse"
                        aria-label="Log out">
                    <x-icon name="logout" class="size-4" />
                </button>
            </form>
        </div>
    </div>
</nav>
