<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Sport Avenue Club</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-navy text-white">
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden overflow-hidden lg:block">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,#1d4ed8,transparent_35%),radial-gradient(circle_at_80%_30%,#84cc16,transparent_28%),linear-gradient(160deg,#0b1d36,#122846)]"></div>
        <div class="relative flex h-full flex-col justify-between p-12">
            <div class="font-display text-2xl font-extrabold">Sport Avenue Club</div>
            <div>
                <div class="font-display text-5xl font-extrabold leading-tight">Book courts.<br>Run the floor.<br>Stay in control.</div>
                <p class="mt-4 max-w-md text-white/70">A staff-first calendar for padel, pickleball, tennis and every other court in the club.</p>
            </div>
            <div class="text-sm text-white/50">Premium court operations · Karachi</div>
        </div>
    </div>
    <div class="flex items-center justify-center bg-mist px-6 py-12 text-ink">
        <div class="card w-full max-w-md p-8">
            <div class="mb-6">
                <div class="font-display text-2xl font-extrabold text-navy">Welcome back</div>
                <p class="text-sm text-slate-500">Sign in to manage today’s bookings.</p>
            </div>
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-semibold">Email</label>
                    <input class="input" type="email" name="email" value="{{ old('email', 'admin@sportavenue.club') }}" required>
                    @error('email')<div class="mt-1 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Password</label>
                    <input class="input" type="password" name="password" value="password" required>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Remember me</label>
                <button class="btn btn-primary w-full">Sign in</button>
            </form>
            <div class="mt-6 rounded-2xl bg-mist p-4 text-xs text-slate-500">
                <div class="font-semibold text-navy">Demo accounts</div>
                admin / manager / staff @ sportavenue.club · password
            </div>
        </div>
    </div>
</div>
</body>
</html>
