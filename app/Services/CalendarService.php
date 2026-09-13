<?php

namespace App\Services;

use App\Enums\HoldStatus;
use App\Enums\RecordStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\Setting;
use App\Support\TimeSlots;
use Carbon\Carbon;

class CalendarService
{
    public function day(string $date, array $filters = []): array
    {
        $slotMinutes = TimeSlots::GRID_MINUTES;
        $courts = $this->courts($filters);
        $nextDate = Carbon::parse($date)->addDay()->toDateString();
        $bookings = Booking::query()
            ->with(['member', 'sport', 'court', 'hold'])
            ->where(function ($q) use ($date, $nextDate) {
                $q->whereDate('booking_date', $date)
                    ->orWhereDate('booking_date', $nextDate);
            })
            ->when(
                ($filters['booking_status'] ?? null) === 'cancelled',
                fn ($q) => $q->where('booking_status', 'cancelled'),
                fn ($q) => $q->when(
                    $filters['booking_status'] ?? null,
                    fn ($inner, $status) => $inner->where('booking_status', $status),
                    fn ($inner) => $inner->where('booking_status', '!=', 'cancelled'),
                ),
            )
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->orderBy('start_time')
            ->get()
            ->groupBy('court_id');

        $blocks = CourtBlock::query()
            ->whereDate('block_date', $date)
            ->where('status', RecordStatus::Active->value)
            ->get()
            ->groupBy('court_id');

        $slots = TimeSlots::generateForCourts($courts, $slotMinutes);

        return [
            'date' => $date,
            'label' => Carbon::parse($date)->format('D d M Y'),
            'slot_duration' => $slotMinutes,
            'slots' => $slots,
            'courts' => $courts->map(function (Court $court) use ($bookings, $blocks, $slots, $slotMinutes, $date) {
                $courtBookings = ($bookings[$court->id] ?? collect())
                    ->filter(fn (Booking $booking) => TimeSlots::sessionDate(
                        $booking->booking_date->toDateString(),
                        $booking->startLabel(),
                        TimeSlots::label((string) $court->opening_time),
                        TimeSlots::label((string) $court->closing_time),
                    ) === $date)
                    ->values();

                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'sport' => [
                        'id' => $court->sport?->id,
                        'name' => $court->sport?->name,
                        'icon' => $court->sport?->icon,
                        'color' => $court->sport?->color,
                    ],
                    'price_per_hour' => $court->price_per_hour,
                    'opening_time' => substr((string) $court->opening_time, 0, 5),
                    'closing_time' => substr((string) $court->closing_time, 0, 5),
                    'bookings' => $courtBookings->map(fn (Booking $booking) => $this->serializeBooking($booking, $slotMinutes))->values(),
                    'blocks' => ($blocks[$court->id] ?? collect())->map(fn (CourtBlock $block) => [
                        'id' => $block->id,
                        'start_time' => substr((string) $block->start_time, 0, 5),
                        'end_time' => substr((string) $block->end_time, 0, 5),
                        'reason' => $block->reason,
                        'slot_span' => TimeSlots::span(substr((string) $block->start_time, 0, 5), substr((string) $block->end_time, 0, 5), $slotMinutes),
                    ])->values(),
                    'cells' => $this->cells($slots, $courtBookings, $blocks[$court->id] ?? collect(), $court, $date, $slotMinutes),
                ];
            })->values(),
        ];
    }

    public function week(string $date, array $filters = []): array
    {
        $start = Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
        $days = collect(range(0, 6))->map(fn ($i) => $this->day($start->copy()->addDays($i)->toDateString(), $filters));

        return [
            'start' => $start->toDateString(),
            'end' => $start->copy()->addDays(6)->toDateString(),
            'label' => $start->format('d M').' — '.$start->copy()->addDays(6)->format('d M Y'),
            'days' => $days,
        ];
    }

    public function month(string $date, array $filters = []): array
    {
        $cursor = Carbon::parse($date)->startOfMonth();
        $end = $cursor->copy()->endOfMonth();
        $courtIds = $this->courts($filters)->pluck('id');

        $counts = Booking::query()
            ->selectRaw('booking_date, booking_status, payment_status, count(*) as total')
            ->whereBetween('booking_date', [$cursor->toDateString(), $end->toDateString()])
            ->when($courtIds->isNotEmpty(), fn ($q) => $q->whereIn('court_id', $courtIds))
            ->when($filters['booking_status'] ?? null, fn ($q, $status) => $q->where('booking_status', $status))
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->groupBy('booking_date', 'booking_status', 'payment_status')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->booking_date)->toDateString());

        $days = [];
        $gridStart = $cursor->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $key = $day->toDateString();
            $rows = $counts[$key] ?? collect();
            $days[] = [
                'date' => $key,
                'in_month' => $day->month === $cursor->month,
                'is_today' => $day->isToday(),
                'total' => $rows->sum('total'),
                'paid' => $rows->where('payment_status', 'fully_paid')->sum('total'),
                'pending' => $rows->where('payment_status', 'pending')->sum('total'),
                'holds' => $rows->where('booking_status', 'on_hold')->sum('total'),
            ];
        }

        return [
            'month' => $cursor->format('F Y'),
            'start' => $cursor->toDateString(),
            'days' => $days,
        ];
    }

    public function events(string $from, string $to, array $filters = []): array
    {
        $courts = $this->courts($filters);
        $window = $this->timeWindow($courts);

        $bookings = Booking::query()
            ->with(['member', 'sport', 'court', 'hold'])
            ->whereDate('booking_date', '>=', Carbon::parse($from)->subDay()->toDateString())
            ->whereDate('booking_date', '<=', Carbon::parse($to)->addDay()->toDateString())
            ->when($filters['court_id'] ?? null, fn ($q, $id) => $q->where('court_id', $id))
            ->when($filters['sport_id'] ?? null, fn ($q, $id) => $q->where('sport_id', $id))
            ->when(
                ($filters['booking_status'] ?? null) === 'cancelled',
                fn ($q) => $q->where('booking_status', 'cancelled'),
                fn ($q) => $q->when(
                    $filters['booking_status'] ?? null,
                    fn ($inner, $status) => $inner->where('booking_status', $status),
                    fn ($inner) => $inner->where('booking_status', '!=', 'cancelled'),
                ),
            )
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        return [
            'slotMinTime' => $window['min'],
            'slotMaxTime' => $window['max'],
            'events' => $bookings->map(function (Booking $booking) {
                $open = TimeSlots::label((string) ($booking->court?->opening_time ?? '17:00'));
                $close = TimeSlots::label((string) ($booking->court?->closing_time ?? '03:00'));
                $tone = $booking->tone();
                $colors = $this->toneColors($tone);
                $payload = $this->serializeBooking($booking);
                $times = $this->fullCalendarRange($booking, $open, $close);

                return [
                    'id' => (string) $booking->id,
                    'title' => ($booking->court?->name ?? 'Court').' · '.($booking->member?->name ?? 'Member'),
                    'start' => $times['start'],
                    'end' => $times['end'],
                    'backgroundColor' => $colors['bg'],
                    'borderColor' => $colors['border'],
                    'textColor' => $colors['text'],
                    'extendedProps' => $payload,
                ];
            })->values(),
        ];
    }

    public function timeWindow($courts = null): array
    {
        return [
            'min' => '00:00:00',
            'max' => '24:00:00',
        ];
    }

    public function serializeBooking(Booking $booking, ?int $slotMinutes = null): array
    {
        $slotMinutes ??= Setting::slotDuration();

        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'date' => $booking->booking_date->toDateString(),
            'start_time' => $booking->startLabel(),
            'end_time' => $booking->endLabel(),
            'duration' => $booking->duration,
            'slot_span' => TimeSlots::span($booking->startLabel(), $booking->endLabel(), $slotMinutes),
            'players_count' => $booking->players_count,
            'sport' => $booking->sport?->only(['id', 'name', 'icon', 'color']),
            'court' => $booking->court?->only(['id', 'name']),
            'member' => $booking->member?->only(['id', 'name', 'member_number', 'phone', 'email']),
            'total_amount' => $booking->total_amount,
            'paid_amount' => $booking->paid_amount,
            'remaining_amount' => $booking->remaining_amount,
            'booking_status' => $booking->booking_status?->value,
            'payment_status' => $booking->payment_status?->value,
            'payment_method' => $booking->payment_method,
            'label' => $booking->combinedLabel(),
            'tone' => $booking->tone(),
            'notes' => $booking->notes,
            'created_at' => $booking->created_at?->toDateTimeString(),
            'created_by' => $booking->creator?->name,
            'cancelled_by' => $booking->canceller?->name,
            'cancelled_at' => $booking->cancelled_at?->toDateTimeString(),
            'cancellation_reason' => $booking->cancellation_reason,
            'hold' => $booking->hold && $booking->hold->status === HoldStatus::Active ? [
                'reason' => $booking->hold->reason,
                'expires_at' => $booking->hold->expires_at?->toDateTimeString(),
                'notes' => $booking->hold->notes,
            ] : null,
        ];
    }

    private function courts(array $filters)
    {
        return Court::query()
            ->with('sport')
            ->where('status', RecordStatus::Active->value)
            ->when($filters['sport_id'] ?? null, fn ($q, $id) => $q->where('sport_id', $id))
            ->when($filters['court_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->orderBy('sport_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function cells($slots, $bookings, $blocks, Court $court, string $date, int $minutes): array
    {
        $cells = [];
        $occupied = [];

        foreach ($slots as $slot) {
            if (in_array($slot, $occupied, true)) {
                continue;
            }

            $booking = $bookings->first(fn (Booking $item) => TimeSlots::covers($item->startLabel(), $item->endLabel(), $slot));
            if ($booking) {
                $span = TimeSlots::span($slot, $booking->endLabel(), $minutes);
                $cells[] = [
                    'slot' => $slot,
                    'type' => 'booking',
                    'span' => $span,
                    'booking' => $this->serializeBooking($booking, $minutes),
                ];
                $this->markOccupied($occupied, $slot, $span, $minutes);
                continue;
            }

            $block = $blocks->first(fn (CourtBlock $item) => TimeSlots::covers(substr((string) $item->start_time, 0, 5), substr((string) $item->end_time, 0, 5), $slot));
            if ($block) {
                $span = TimeSlots::span($slot, substr((string) $block->end_time, 0, 5), $minutes);
                $cells[] = [
                    'slot' => $slot,
                    'type' => 'block',
                    'span' => $span,
                    'reason' => $block->reason,
                ];
                $this->markOccupied($occupied, $slot, $span, $minutes);
                continue;
            }

            $covered = $bookings->first(fn (Booking $item) => TimeSlots::covers($item->startLabel(), $item->endLabel(), $slot))
                || $blocks->first(fn (CourtBlock $item) => TimeSlots::covers(substr((string) $item->start_time, 0, 5), substr((string) $item->end_time, 0, 5), $slot));

            if ($covered) {
                continue;
            }

            $insideHours = TimeSlots::inHours(
                $slot,
                TimeSlots::label((string) $court->opening_time),
                TimeSlots::label((string) $court->closing_time),
            );

            $cells[] = [
                'slot' => $slot,
                'type' => $insideHours ? 'available' : 'closed',
                'span' => 1,
                'date' => $date,
                'court_id' => $court->id,
                'sport_id' => $court->sport_id,
            ];
        }

        return $cells;
    }

    /**
     * @return array{start: string, end: string}
     */
    private function fullCalendarRange(Booking $booking, string $open, string $close): array
    {
        [$start, $end] = TimeSlots::dateRange(
            $booking->booking_date->toDateString(),
            $booking->startLabel(),
            $booking->endLabel(),
        );

        return [
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
        ];
    }

    /**
     * @return array{bg: string, border: string, text: string}
     */
    private function toneColors(string $tone): array
    {
        return match ($tone) {
            'paid' => ['bg' => '#86efac', 'border' => '#22c55e', 'text' => '#14532d'],
            'partial' => ['bg' => '#fdba74', 'border' => '#f97316', 'text' => '#7c2d12'],
            'hold' => ['bg' => '#c4b5fd', 'border' => '#7c3aed', 'text' => '#4c1d95'],
            'cancelled' => ['bg' => '#fca5a5', 'border' => '#dc2626', 'text' => '#7f1d1d'],
            default => ['bg' => '#fde047', 'border' => '#eab308', 'text' => '#713f12'],
        };
    }

    private function markOccupied(array &$occupied, string $start, int $span, int $minutes): void
    {
        $cursor = Carbon::parse($start);
        for ($i = 0; $i < $span; $i++) {
            $occupied[] = $cursor->format('H:i');
            $cursor->addMinutes($minutes);
        }
    }
}
