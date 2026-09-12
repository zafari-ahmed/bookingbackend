@extends('layouts.app')
@section('title', 'Sports & Courts')
@section('content')
<div class="mb-6 flex items-end justify-between">
    <div>
        <div class="text-sm font-semibold uppercase tracking-wide text-electric">Facilities</div>
        <h1 class="font-display text-3xl font-extrabold text-navy">Sports & Courts</h1>
    </div>
    @if (auth()->user()->canManageFacilities())
        <div class="flex gap-2" x-data="{sport:false,court:false}">
            <button class="btn btn-ghost" @click="sport=true">+ Sport</button>
            <button class="btn btn-primary" @click="court=true">+ Court</button>
            <div x-show="sport" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-navy/40 p-4">
                <form method="POST" action="{{ route('sports.store') }}" class="card w-full max-w-md space-y-3 p-6">
                    @csrf
                    <h3 class="font-display text-xl font-extrabold">Add sport</h3>
                    <input class="input" name="name" placeholder="Name" required>
                    <input class="input" name="icon" placeholder="Icon (emoji)">
                    <input class="input" name="description" placeholder="Description">
                    <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" @click="sport=false">Cancel</button><button class="btn btn-primary">Save</button></div>
                </form>
            </div>
            <div x-show="court" x-cloak class="fixed inset-0 z-40 flex items-center justify-center bg-navy/40 p-4">
                <form method="POST" action="{{ route('courts.store') }}" class="card w-full max-w-md space-y-3 p-6">
                    @csrf
                    <h3 class="font-display text-xl font-extrabold">Add court</h3>
                    <select class="select" name="sport_id" required>
                        @foreach ($sports as $sport)<option value="{{ $sport->id }}">{{ $sport->name }}</option>@endforeach
                    </select>
                    <input class="input" name="name" placeholder="Court name" required>
                    <input class="input" name="price_per_hour" placeholder="Hourly price" required>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Opens</label>
                            <input class="input" type="time" name="opening_time" value="17:00">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Closes</label>
                            <input class="input" type="time" name="closing_time" value="03:00">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">24-hour time. If close is earlier than open (5:00 PM–3:00 AM), the court stays open past midnight. Bookings after 12:00 AM save on the next day.</p>
                    <input class="input" name="description" placeholder="Description">
                    <div class="flex justify-end gap-2"><button type="button" class="btn btn-ghost" @click="court=false">Cancel</button><button class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
<div class="grid gap-4 lg:grid-cols-2">
    @foreach ($sports as $sport)
        <div class="card p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <div class="font-display text-2xl font-extrabold">{{ $sport->icon }} {{ $sport->name }}</div>
                    <div class="text-sm text-slate-500">{{ $sport->courts->count() }} courts · {{ $sport->status }}</div>
                </div>
                @if (auth()->user()->canManageFacilities())
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('sports.update', $sport) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="name" value="{{ $sport->name }}">
                            <input type="hidden" name="icon" value="{{ $sport->icon }}">
                            <input type="hidden" name="status" value="{{ $sport->status === 'active' ? 'inactive' : 'active' }}">
                            <button class="btn btn-soft" onclick="return confirm('Change sport status?')">{{ $sport->status === 'active' ? 'Disable' : 'Enable' }}</button>
                        </form>
                        <form method="POST" action="{{ route('sports.destroy', $sport) }}" onsubmit="return confirm('Delete {{ $sport->name }} and its courts? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                @endif
            </div>
            <div class="space-y-3">
                @foreach ($sport->courts as $court)
                    <div class="rounded-2xl bg-mist p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="font-bold">{{ $court->name }}</div>
                                <div class="text-sm text-slate-500">Rs. {{ number_format($court->price_per_hour, 0) }}/hr · {{ substr($court->opening_time,0,5) }}–{{ substr($court->closing_time,0,5) }}</div>
                            </div>
                            <span class="badge {{ $court->status==='active' ? 'badge-paid' : 'badge-cancelled' }}">{{ $court->status }}</span>
                        </div>
                        @if (auth()->user()->canManageFacilities())
                            <form method="POST" action="{{ route('courts.update', $court) }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="sport_id" value="{{ $court->sport_id }}">
                                <input class="input" name="name" value="{{ $court->name }}">
                                <input class="input" name="price_per_hour" value="{{ $court->price_per_hour }}">
                                <input class="input" type="time" name="opening_time" value="{{ substr($court->opening_time,0,5) }}">
                                <input class="input" type="time" name="closing_time" value="{{ substr($court->closing_time,0,5) }}">
                                <input class="input sm:col-span-2" name="description" value="{{ $court->description }}">
                                <input type="hidden" name="status" value="{{ $court->status }}">
                                <button class="btn btn-ghost">Save court</button>
                            </form>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('courts.toggle', $court) }}" onsubmit="return confirm('Disable or enable this court?')">
                                    @csrf
                                    <button class="btn btn-soft">{{ $court->status==='active' ? 'Disable court' : 'Enable court' }}</button>
                                </form>
                                <form method="POST" action="{{ route('courts.destroy', $court) }}" onsubmit="return confirm('Delete this court and its bookings? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Delete court</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
