@extends('layouts.app')
@section('title', $member->name)
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <div class="text-sm font-semibold uppercase tracking-wide text-electric">Member profile</div>
        <h1 class="font-display text-3xl font-extrabold text-navy">{{ $member->name }}</h1>
        <div class="text-slate-500">{{ $member->member_number }} · {{ $member->phone }}</div>
    </div>
    <a href="{{ route('bookings.create') }}" class="btn btn-primary">Book for member</a>
</div>
<div class="grid gap-4 xl:grid-cols-3">
    <div class="card p-5 xl:col-span-1">
        <form method="POST" action="{{ route('members.update', $member) }}" class="space-y-3">
            @csrf @method('PUT')
            <input class="input" name="name" value="{{ $member->name }}" required>
            <input class="input" name="member_number" value="{{ $member->member_number }}">
            <input class="input" name="phone" value="{{ $member->phone }}" required>
            <input class="input" name="email" value="{{ $member->email }}">
            <select class="select" name="gender">
                <option value="">Gender</option>
                @foreach (['male','female','other'] as $g)
                    <option value="{{ $g }}" @selected($member->gender===$g)>{{ ucfirst($g) }}</option>
                @endforeach
            </select>
            <input class="input" type="date" name="dob" value="{{ optional($member->dob)->toDateString() }}">
            <textarea class="input" name="notes">{{ $member->notes }}</textarea>
            <button class="btn btn-primary w-full">Save details</button>
        </form>
        @if (auth()->user()->isAdmin())
            <form method="POST" action="{{ route('members.destroy', $member) }}" class="mt-3" onsubmit="return confirm('Disable or delete this member?')">
                @csrf @method('DELETE')
                <button class="btn btn-danger w-full">Delete / disable</button>
            </form>
        @endif
    </div>
    <div class="xl:col-span-2 space-y-4">
        <div class="grid gap-3 sm:grid-cols-5">
            @foreach ([['Total', $stats['total']], ['Completed', $stats['completed']], ['Cancelled', $stats['cancelled']], ['Spent', 'Rs. '.number_format($stats['spent'],0)], ['Outstanding', 'Rs. '.number_format($stats['outstanding'],0)]] as [$label, $value])
                <div class="card p-4">
                    <div class="text-xs text-slate-500">{{ $label }}</div>
                    <div class="font-display text-xl font-extrabold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <div class="card p-5">
            <form class="mb-4 flex flex-wrap gap-2">
                <input type="date" class="input w-auto" name="from" value="{{ request('from') }}">
                <input type="date" class="input w-auto" name="to" value="{{ request('to') }}">
                <select class="select w-auto" name="sport_id">
                    <option value="">All sports</option>
                    @foreach ($sports as $sport)<option value="{{ $sport->id }}" @selected(request('sport_id')==$sport->id)>{{ $sport->name }}</option>@endforeach
                </select>
                <select class="select w-auto" name="status">
                    <option value="">All statuses</option>
                    <option value="confirmed">Booked</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="on_hold">On Hold</option>
                </select>
                <button class="btn btn-ghost">Filter</button>
            </form>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-mist text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2">Date</th><th class="px-3 py-2">Sport</th><th class="px-3 py-2">Court</th>
                            <th class="px-3 py-2">Time</th><th class="px-3 py-2">Amount</th><th class="px-3 py-2">Payment</th><th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $booking)
                            <tr class="border-t border-slate-100">
                                <td class="px-3 py-2">{{ $booking->booking_date->toDateString() }}</td>
                                <td class="px-3 py-2">{{ $booking->sport?->name }}</td>
                                <td class="px-3 py-2">{{ $booking->court?->name }}</td>
                                <td class="px-3 py-2">{{ $booking->startLabel() }}–{{ $booking->endLabel() }}</td>
                                <td class="px-3 py-2">Rs. {{ number_format($booking->total_amount, 0) }}</td>
                                <td class="px-3 py-2"><span class="badge badge-{{ $booking->tone() }}">{{ $booking->payment_status?->label() }}</span></td>
                                <td class="px-3 py-2"><span class="badge badge-{{ $booking->tone() }}">{{ $booking->combinedLabel() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No bookings match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $history->links() }}</div>
        </div>
    </div>
</div>
@endsection
