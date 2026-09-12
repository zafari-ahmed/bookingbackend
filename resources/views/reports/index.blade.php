@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div x-data="reportsPage(@js($charts))">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3 no-print">
        <div>
            <div class="text-sm font-semibold uppercase tracking-wide text-electric">Operations intelligence</div>
            <h1 class="font-display text-3xl font-extrabold text-navy">Reports</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn btn-soft" href="{{ route('reports.export', ['format' => 'xlsx'] + request()->query()) }}">Export Excel</a>
            <a class="btn btn-soft" href="{{ route('reports.export', ['format' => 'csv'] + request()->query()) }}">Export CSV</a>
            <a class="btn btn-soft" href="{{ route('reports.export', ['format' => 'pdf'] + request()->query()) }}">Export PDF</a>
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
    </div>

    <form class="card mb-5 grid gap-3 p-4 md:grid-cols-6 no-print">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">From date</label>
            <input class="input" type="date" name="from" value="{{ $filters['from'] }}">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To date</label>
            <input class="input" type="date" name="to" value="{{ $filters['to'] }}">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Member</label>
            <input class="input" name="member" value="{{ $filters['member'] }}" placeholder="Number, name or phone">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sport</label>
            <select class="select" name="sport_id">
                <option value="">All sports</option>
                @foreach ($sports as $sport)<option value="{{ $sport->id }}" @selected(($filters['sport_id']??'')==$sport->id)>{{ $sport->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Court</label>
            <select class="select" name="court_id">
                <option value="">All courts</option>
                @foreach ($courts as $court)<option value="{{ $court->id }}" @selected(($filters['court_id']??'')==$court->id)>{{ $court->name }}</option>@endforeach
            </select>
        </div>
        <!-- <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Start time</label>
            <input class="input" type="time" name="start_time" value="{{ $filters['start_time'] }}">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">End time</label>
            <input class="input" type="time" name="end_time" value="{{ $filters['end_time'] }}">
        </div> -->
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Specific hour</label>
            <select class="select" name="hour">
                <option value="">Any hour</option>
                @for ($h=8;$h<=22;$h++)
                    <option value="{{ $h }}" @selected(($filters['hour']??'')==$h)>{{ sprintf('%02d:00', $h) }}</option>
                @endfor
            </select>
        </div>
        <div class="flex items-end md:col-span-2">
            <button class="btn btn-primary w-full">Generate report</button>
        </div>
    </form>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ["Total bookings", $summary['total_bookings']],
            ["Fully paid", $summary['fully_paid']],
            ["Partial paid", $summary['partial_paid']],
            ["Pending", $summary['pending']],
            ["Cancelled", $summary['cancelled']],
            ["Revenue", $money['total_revenue']],
            ["Collected", $money['collected']],
            ["Outstanding", $money['outstanding']],
        ] as [$label, $value])
            <div class="card p-4">
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="font-display text-2xl font-extrabold">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 grid gap-4 xl:grid-cols-2">
        <div class="card p-4"><div class="mb-2 font-bold">Bookings by sport</div><div class="h-56"><canvas id="sportChart"></canvas></div></div>
        <div class="card p-4"><div class="mb-2 font-bold">Bookings by day</div><div class="h-56"><canvas id="dayChart"></canvas></div></div>
        <div class="card p-4"><div class="mb-2 font-bold">Revenue by month</div><div class="h-56"><canvas id="monthChart"></canvas></div></div>
        <div class="card p-4"><div class="mb-2 font-bold">Payment status</div><div class="h-56"><canvas id="payChart"></canvas></div></div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <div class="card p-5">
            <h3 class="font-display text-xl font-extrabold">Peak hours</h3>
            @forelse ($charts['peak_hours'] as $hour => $count)
                <div class="mt-3">
                    <div class="mb-1 flex justify-between text-sm"><span>{{ $hour }}</span><span class="font-bold">{{ $count }} bookings</span></div>
                    <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-electric" style="width: {{ min(100, $count * 4) }}%"></div></div>
                </div>
            @empty
                <p class="mt-3 text-sm text-slate-500">No peak-hour data for this range.</p>
            @endforelse
        </div>
        <div class="card p-5">
            <h3 class="font-display text-xl font-extrabold">Court utilization</h3>
            @foreach ($charts['utilization'] as $row)
                <div class="mt-3">
                    <div class="mb-1 flex justify-between text-sm"><span>{{ $row['name'] }}</span><span class="font-bold">{{ $row['percent'] }}%</span></div>
                    <div class="h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-lime" style="width: {{ $row['percent'] }}%"></div></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mt-5 overflow-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-mist text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-3 py-2">Booking</th><th class="px-3 py-2">Date</th><th class="px-3 py-2">Sport</th>
                    <th class="px-3 py-2">Court</th><th class="px-3 py-2">Member</th><th class="px-3 py-2">Time</th>
                    <th class="px-3 py-2">Amount</th><th class="px-3 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2 font-semibold">{{ $booking->booking_number }}</td>
                        <td class="px-3 py-2">{{ $booking->booking_date->toDateString() }}</td>
                        <td class="px-3 py-2">{{ $booking->sport?->name }}</td>
                        <td class="px-3 py-2">{{ $booking->court?->name }}</td>
                        <td class="px-3 py-2">{{ $booking->member?->name }}<div class="text-xs text-slate-400">{{ $booking->member?->member_number }}</div></td>
                        <td class="px-3 py-2">{{ $booking->startLabel() }}–{{ $booking->endLabel() }}</td>
                        <td class="px-3 py-2">Rs. {{ number_format($booking->paid_amount, 0) }}</td>
                        <td class="px-3 py-2"><span class="badge badge-{{ $booking->tone() }}">{{ $booking->combinedLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-10 text-center text-slate-500">No bookings found for the selected filters.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 no-print">{{ $bookings->links() }}</div>
    </div>
</div>
@endsection
