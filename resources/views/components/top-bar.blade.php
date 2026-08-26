@props([
    'title' => 'Dashboard',
    'unreadCount' => 0,
    'preview' => null,
    'actions' => null,
])

{{--
    Identical on every inner page (design-system.md §4): page title left,
    centred global search, bell + "as of" timestamp + primary action right.
--}}
<header class="sticky top-0 z-30 flex flex-wrap items-center gap-3 border-b border-border bg-cream px-4 py-3 sm:px-8">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button type="button" x-data @click="$store.sidebar.openDrawer()"
                class="flex size-11 items-center justify-center rounded-control border border-border bg-surface text-text-secondary md:hidden"
                aria-label="Open navigation">
            <x-icon name="menu" class="size-5" />
        </button>

        <h1 class="truncate text-lg font-extrabold tracking-tight text-navy sm:text-xl">{{ $title }}</h1>
    </div>

    {{-- Global search: name, CNIC or case ID, across every case in scope. --}}
    <form method="GET" action="{{ route('cases.index') }}"
          class="order-last w-full min-w-0 sm:order-none sm:w-auto sm:flex-1 sm:max-w-md">
        <label for="global-search" class="sr-only">Search cases by name, CNIC or case ID</label>
        <div class="flex items-center gap-2 rounded-full border border-border bg-surface px-4 focus-within:border-teal">
            <x-icon name="search" class="size-4 shrink-0 text-text-muted" />
            <input id="global-search" type="search" name="q" value="{{ request('q') }}"
                   placeholder="Search by name, CNIC or case ID"
                   class="min-h-11 w-full min-w-0 border-0 bg-transparent text-sm text-text-primary outline-none placeholder:text-text-muted">
        </div>
    </form>

    <div class="flex shrink-0 items-center gap-3">
        <p class="hidden text-right text-[11.5px] leading-tight text-text-muted lg:block">
            As of {{ now()->timezone(config('app.timezone'))->format('d M Y, g:i A') }}
        </p>

        <x-notification-bell :unread-count="$unreadCount" :preview="$preview" />

        {{ $actions }}
    </div>
</header>
