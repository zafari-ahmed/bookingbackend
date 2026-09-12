@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<div class="mb-6">
    <div class="text-sm font-semibold uppercase tracking-wide text-electric">Club controls</div>
    <h1 class="font-display text-3xl font-extrabold text-navy">Settings</h1>
</div>
<div class="grid gap-5 xl:grid-cols-2">
    <form method="POST" action="{{ route('settings.update') }}" class="card space-y-3 p-5">
        @csrf @method('PUT')
        <h2 class="font-display text-xl font-extrabold">General</h2>
        <input class="input" name="club_name" value="{{ $settings['club_name'] }}">
        <select class="select" name="slot_duration">
            @foreach ([30,60,90,120] as $mins)
                <option value="{{ $mins }}" @selected($settings['slot_duration']==$mins)>{{ $mins }} minute slots</option>
            @endforeach
        </select>
        <input class="input" name="currency" value="{{ $settings['currency'] }}">
        <div class="grid grid-cols-2 gap-2">
            <input class="input" type="time" name="default_open" value="{{ $settings['default_open'] }}">
            <input class="input" type="time" name="default_close" value="{{ $settings['default_close'] }}">
        </div>
        <button class="btn btn-primary">Save settings</button>
    </form>
    <form method="POST" action="{{ route('settings.users.store') }}" class="card space-y-3 p-5">
        @csrf
        <h2 class="font-display text-xl font-extrabold">Add staff</h2>
        <input class="input" name="name" placeholder="Name" required>
        <input class="input" name="email" placeholder="Email" required>
        <input class="input" name="password" type="password" placeholder="Password" required>
        <select class="select" name="role">
            <option value="staff">Staff</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
        </select>
        <button class="btn btn-primary">Create account</button>
    </form>
</div>
<div class="card mt-5 overflow-auto">
    <div class="p-4 font-display text-xl font-extrabold">Staff accounts</div>
    <table class="min-w-full text-sm">
        <thead class="bg-mist text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Email</th><th class="px-4 py-2">Role</th><th class="px-4 py-2">Status</th><th class="px-4 py-2"></th></tr></thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-3" colspan="5">
                        <form method="POST" action="{{ route('settings.users.update', $user) }}" class="grid gap-2 md:grid-cols-5">
                            @csrf @method('PUT')
                            <input class="input" name="name" value="{{ $user->name }}">
                            <input class="input" name="email" value="{{ $user->email }}">
                            <select class="select" name="role">
                                @foreach (['admin','manager','staff'] as $role)
                                    <option value="{{ $role }}" @selected($user->role?->value===$role)>{{ ucfirst($role) }}</option>
                                @endforeach
                            </select>
                            <select class="select" name="status">
                                <option value="active" @selected($user->status==='active')>Active</option>
                                <option value="inactive" @selected($user->status==='inactive')>Inactive</option>
                            </select>
                            <button class="btn btn-ghost">Save</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="card mt-5 p-5" x-data="auditTable(@js($logs))">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-xl font-extrabold">Audit log</h2>
        <input class="input max-w-xs" type="search" placeholder="Search logs…" x-model="query" @input="page=1">
    </div>
    <div class="overflow-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-mist text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-3">Time</th>
                    <th class="px-3 py-3">User</th>
                    <th class="px-3 py-3">Action</th>
                    <th class="px-3 py-3">Description</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="log in pageRows" :key="log.id">
                    <tr class="border-t border-slate-100">
                        <td class="whitespace-nowrap px-3 py-3 text-slate-500" x-text="log.time"></td>
                        <td class="px-3 py-3 font-semibold" x-text="log.user"></td>
                        <td class="px-3 py-3"><span class="badge badge-navy" x-text="log.action"></span></td>
                        <td class="px-3 py-3" x-text="log.description"></td>
                    </tr>
                </template>
                <tr x-show="!pageRows.length">
                    <td colspan="4" class="px-3 py-8 text-center text-slate-500">No activity logs match this search.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
        <div x-text="summary"></div>
        <div class="flex gap-2">
            <button class="btn btn-ghost" :disabled="page<=1" @click="page--">Prev</button>
            <button class="btn btn-ghost" :disabled="page>=pages" @click="page++">Next</button>
        </div>
    </div>
</div>
@endsection
