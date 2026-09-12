@extends('layouts.app')
@section('title', 'Members')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <div class="text-sm font-semibold uppercase tracking-wide text-electric">Club directory</div>
        <h1 class="font-display text-3xl font-extrabold text-navy">Members</h1>
    </div>
    <div class="flex flex-wrap gap-2" x-data="{open:false}">
        <form class="flex gap-2" method="GET">
            <input class="input w-72" name="q" value="{{ $q }}" placeholder="Name, phone or member number">
            <button class="btn btn-ghost">Search</button>
        </form>
        <button type="button" class="btn btn-primary" @click="open=true">+ Add member</button>
        <div x-show="open" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-navy/40 p-4" @click.self="open=false">
            <form method="POST" action="{{ route('members.store') }}" class="card w-full max-w-lg space-y-3 p-6" @click.stop>
                @csrf
                <h3 class="font-display text-xl font-extrabold">Create member</h3>
                <input class="input" name="name" placeholder="Name" required>
                <input class="input" name="phone" placeholder="Phone" required>
                <input class="input" name="member_number" placeholder="Member number (optional)">
                <input class="input" name="email" placeholder="Email">
                <select class="select" name="gender">
                    <option value="">Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
                <textarea class="input" name="notes" placeholder="Notes"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-ghost" @click="open=false">Cancel</button>
                    <button class="btn btn-primary">Save member</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="card overflow-hidden">
    @if ($members->isEmpty())
        <div class="p-6">@include('components.empty-state', ['title' => 'No members found', 'copy' => 'Try another search or create a new member.'])</div>
    @else
        <table class="min-w-full text-sm">
            <thead class="bg-mist text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Member</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Bookings</th>
                    <th class="px-4 py-3">Last booking</th>
                    <th class="px-4 py-3">Spent</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($members as $member)
                    <tr class="border-t border-slate-100 hover:bg-mist/70">
                        <td class="px-4 py-3">
                            <a href="{{ route('members.show', $member) }}" class="font-bold text-navy">{{ $member->name }}</a>
                            <div class="text-xs text-slate-500">{{ $member->member_number }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $member->phone }}</td>
                        <td class="px-4 py-3">{{ $member->bookings_count }}</td>
                        <td class="px-4 py-3">{{ $member->last_booking ?: '—' }}</td>
                        <td class="px-4 py-3 font-semibold">Rs. {{ number_format($member->total_spent, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $members->links() }}</div>
    @endif
</div>
@endsection
