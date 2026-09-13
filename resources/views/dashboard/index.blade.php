@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <div class="text-sm font-semibold uppercase tracking-wide text-electric">Today’s floor</div>
        <h1 class="font-display text-3xl font-extrabold text-navy">{{ $todayLabel }}</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('bookings.create') }}" class="btn btn-lime">+ New booking</a>
        <a href="{{ route('calendar.index') }}" class="btn btn-primary">Open booking calendar</a>
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cards as $card)
        <div class="card stat-card tone-{{ $card['tone'] }} p-5">
            <div class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</div>
            <div class="mt-2 font-display text-3xl font-extrabold text-navy">{{ $card['value'] }}</div>
        </div>
    @endforeach
</div>

<div class="mt-8" x-data="calendarPage(@js(['date' => $date, 'view' => 'day', 'sports' => $sports, 'courts' => $courts, 'compact' => true, 'fullReload' => true]))">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-display text-xl font-extrabold">Today’s schedule</h2>
        <button class="btn btn-lime" @click="bookingOpen=true; form.booking_date=date">+ New Booking</button>
    </div>
    <div class="card mb-4 flex flex-wrap items-center gap-3 p-3">
        <select class="select max-w-40" x-model="filters.sport_id" @change="load()">
            <option value="">All sports</option>
            @foreach ($sports as $sport)<option value="{{ $sport->id }}">{{ $sport->icon }} {{ $sport->name }}</option>@endforeach
        </select>
        <select class="select max-w-48" x-model="filters.court_id" @change="load()">
            <option value="">All courts</option>
            <template x-for="court in filteredCourts" :key="court.id">
                <option :value="court.id" x-text="court.name"></option>
            </template>
        </select>
        <select class="select max-w-40" x-model="filters.booking_status" @change="load()">
            <option value="">All statuses</option>
            <option value="confirmed">Booked</option>
            <option value="on_hold">On Hold</option>
            <option value="cancelled">Cancelled</option>
            <option value="completed">Completed</option>
        </select>
        <select class="select max-w-40" x-model="filters.payment_status" @change="load()">
            <option value="">All payments</option>
            <option value="fully_paid">Fully Paid</option>
            <option value="partial_paid">Partial Paid</option>
            <option value="pending">Pending</option>
        </select>
    </div>
    <div class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
        <span class="badge badge-available">Available</span>
        <span class="badge badge-paid">Booked • Fully Paid</span>
        <span class="badge badge-partial">Booked • Partial Paid</span>
        <span class="badge badge-pending">Booked • Pending</span>
        <span class="badge badge-hold">On Hold</span>
        <span class="badge badge-cancelled">Cancelled</span>
    </div>
    @include('calendar.partials.fullcalendar')
    @include('calendar.partials.drawer')
    @include('calendar.partials.modals')
</div>
@endsection
