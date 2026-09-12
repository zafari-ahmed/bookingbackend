@extends('layouts.app')
@section('title', 'New Booking')
@section('content')
<div class="mx-auto max-w-3xl" x-data="bookingForm(@js(['sports' => $sports, 'prefill' => $prefill]))">
    <div class="mb-6">
        <div class="text-sm font-semibold uppercase tracking-wide text-electric">Quick book</div>
        <h1 class="font-display text-3xl font-extrabold text-navy">New booking</h1>
    </div>
    <div class="card p-6">
        @include('bookings.partials.fields')
        <div class="mt-5 flex justify-end gap-2">
            <a href="{{ route('calendar.index') }}" class="btn btn-ghost">Back to calendar</a>
            <button class="btn btn-primary" :disabled="saving" @click="submit()">Confirm booking</button>
        </div>
    </div>
    <div x-show="conflict" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-navy/40 p-4">
        <div class="card w-full max-w-lg p-6">
            <h3 class="font-display text-2xl font-extrabold">Slot unavailable</h3>
            <p class="mt-2 text-slate-600">This court is already booked for the selected time.</p>
            <div class="mt-4 flex justify-end gap-2">
                <button class="btn btn-ghost" @click="conflict=null">Choose another slot</button>
                <a class="btn btn-primary" :href="'/calendar?date='+form.booking_date">View calendar</a>
            </div>
        </div>
    </div>
</div>
@endsection
