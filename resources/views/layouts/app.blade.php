<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ $clubName ?? 'Sport Avenue Club' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="club-shell antialiased" x-data="clubShell()">
<script>
    window.Club = {
        csrf: @json(csrf_token()),
        unread: {{ (int) ($unreadNotifications ?? 0) }},
        user: {
            name: @json(auth()->user()->name),
            role: @json(auth()->user()->role?->value),
            canCancel: @json(auth()->user()->canCancelBookings()),
            canHold: @json(auth()->user()->canManageHolds()),
        }
    };
</script>
<div class="flex min-h-screen">
    @include('layouts.partials.sidebar')
    <div class="flex min-w-0 flex-1 flex-col">
        @include('layouts.partials.topnav')
        <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded-2xl bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
@include('layouts.partials.overlays')
</body>
</html>
