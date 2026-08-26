<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} · {{ config('app.name') }}</title>

    {{--
        Deliberately inline and blocking: the sidebar's collapsed width has to
        be known before the first frame is painted, which is earlier than any
        bundled script can run. Deferring it to Alpine made the sidebar visibly
        snap into place on every navigation.
    --}}
    <script>
        (function () {
            var collapsed = window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches;

            try {
                // Tablet always uses the icon rail (design-system.md §7); on
                // wider screens the officer's own choice wins.
                if (! collapsed) {
                    collapsed = window.localStorage.getItem('sidebar-collapsed') === 'true';
                }
            } catch (error) {
                // Storage blocked (private mode); fall back to the media query.
            }

            if (collapsed) {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream font-sans text-text-primary">
    <div class="flex min-h-screen">
        <x-sidebar />

        {{-- Mobile: slide-out drawer over a dark scrim (design-system.md §7). --}}
        <div
            x-data
            x-show="$store.sidebar.drawerOpen"
            x-cloak
            class="fixed inset-0 z-50 flex md:hidden"
        >
            <div
                x-show="$store.sidebar.drawerOpen"
                x-transition.opacity
                @click="$store.sidebar.closeDrawer()"
                class="absolute inset-0 bg-navy/60"
                aria-hidden="true"
            ></div>

            <div
                x-show="$store.sidebar.drawerOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="relative w-[260px] max-w-[84vw]"
            >
                <x-sidebar :drawer="true" />
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <x-top-bar
                :title="$title ?? 'Dashboard'"
                :unread-count="$unreadNotificationCount ?? 0"
                :preview="$notificationPreview ?? collect()"
            >
                <x-slot:actions>{{ $actions ?? '' }}</x-slot:actions>
            </x-top-bar>

            <main class="min-w-0 flex-1 overflow-x-clip px-4 pb-16 pt-6 sm:px-8">
                <x-flash />

                @if ($backLink ?? false)
                    <a href="{{ $backLink }}"
                       class="mb-4 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-referred-text hover:text-navy">
                        <span aria-hidden="true">&larr;</span> {{ $backLabel ?? 'Back to Dashboard' }}
                    </a>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
