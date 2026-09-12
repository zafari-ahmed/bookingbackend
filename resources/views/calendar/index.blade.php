@extends('layouts.app')
@section('title', 'Booking Calendar')
@section('content')
<div x-data="calendarPage(@js(['date' => $date, 'view' => $view, 'sports' => $sports, 'courts' => $courts]))">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-sm font-semibold uppercase tracking-wide text-electric">Live floor</div>
            <h1 class="font-display text-3xl font-extrabold text-navy" x-text="dateLabel"></h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <button class="btn btn-soft" @click="shift(-1)">Prev</button>
            <button class="btn btn-ghost" @click="today()">Today</button>
            <button class="btn btn-soft" @click="shift(1)">Next</button>
            <input type="date" class="input w-auto" x-model="date" @change="load()">
            <button class="btn btn-lime" @click="bookingOpen=true; form.booking_date=date">+ New Booking</button>
        </div>
    </div>

    <div class="card mb-4 flex flex-wrap items-center gap-3 p-3">
        <div class="flex rounded-2xl bg-mist p-1">
            <template x-for="item in ['day','week','month']">
                <button class="btn" :class="view===item ? 'btn-primary' : 'btn-ghost'" @click="setView(item)" x-text="item[0].toUpperCase()+item.slice(1)"></button>
            </template>
        </div>
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
        <div class="ml-auto" x-show="view==='day'">
            @include('calendar.partials.layout-toggle')
        </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
        <span class="badge badge-available">Available</span>
        <span class="badge badge-paid">Booked • Fully Paid</span>
        <span class="badge badge-partial">Booked • Partial Paid</span>
        <span class="badge badge-pending">Booked • Pending</span>
        <span class="badge badge-hold">On Hold</span>
        <span class="badge badge-cancelled">Cancelled</span>
    </div>

    @include('calendar.partials.day-grid')

    <div x-show="!loading && view==='week'" class="grid gap-4 lg:grid-cols-7">
        <template x-for="day in data.days" :key="day.date">
            <div class="card p-3">
                <button class="mb-3 w-full text-left font-bold text-navy" @click="date=day.date; setView('day')" x-text="day.label"></button>
                <template x-for="court in day.courts" :key="day.date+court.id">
                    <div class="mb-3">
                        <div class="text-xs font-semibold text-slate-400" x-text="court.name"></div>
                        <template x-for="booking in court.bookings" :key="booking.id">
                            <button class="mt-1 w-full rounded-xl px-2 py-1 text-left text-xs" :class="'slot-'+booking.tone" @click="openBooking(booking.id)">
                                <div class="font-bold" x-text="booking.member?.name"></div>
                                <div x-text="timeLabel(booking.start_time)+' · '+booking.label"></div>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <div x-show="!loading && view==='month'" class="card p-4">
        <div class="mb-3 font-display text-xl font-extrabold" x-text="data.month"></div>
        <div class="grid grid-cols-7 gap-2">
            <template x-for="label in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']">
                <div class="text-center text-xs font-bold uppercase text-slate-400" x-text="label"></div>
            </template>
            <template x-for="day in data.days" :key="day.date">
                <button class="min-h-24 rounded-2xl border p-2 text-left" :class="day.in_month ? 'bg-white' : 'bg-mist text-slate-400'" @click="date=day.date; setView('day')">
                    <div class="font-bold" x-text="day.date.slice(-2)"></div>
                    <div class="text-xs" x-text="day.total+' bookings'"></div>
                    <div class="mt-1 h-1.5 rounded-full bg-green-400" :style="`width:${Math.min(100, day.paid*8)}%`"></div>
                </button>
            </template>
        </div>
    </div>

    @include('calendar.partials.drawer')
    @include('calendar.partials.modals')
</div>
@endsection
