<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Support\Money;
use App\Support\TimeSlots;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function filtersFromRequest(array $input): array
    {
        [$from, $to] = $this->dateRange($input);

        return [
            'from' => $from,
            'to' => $to,
            'member' => $input['member'] ?? null,
            'sport_id' => $input['sport_id'] ?? null,
            'court_id' => $input['court_id'] ?? null,
            'start_time' => $input['start_time'] ?? null,
            'end_time' => $input['end_time'] ?? null,
            'hour' => $input['hour'] ?? null,
            'booking_status' => $input['booking_status'] ?? null,
            'payment_status' => $input['payment_status'] ?? null,
        ];
    }

    public function query(array $filters): Builder
    {
        return Booking::query()
            ->with(['member', 'sport', 'court', 'creator'])
            ->whereBetween('booking_date', [$filters['from'], $filters['to']])
            ->when($filters['sport_id'] ?? null, fn ($q, $id) => $q->where('sport_id', $id))
            ->when($filters['court_id'] ?? null, fn ($q, $id) => $q->where('court_id', $id))
            ->when($filters['booking_status'] ?? null, fn ($q, $status) => $q->where('booking_status', $status))
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($filters['start_time'] ?? null, fn ($q, $time) => $q->where('start_time', '>=', $time))
            ->when($filters['end_time'] ?? null, fn ($q, $time) => $q->where('end_time', '<=', $time))
            ->when($filters['hour'] ?? null, function ($q, $hour) {
                $start = sprintf('%02d:00:00', (int) $hour);
                $end = sprintf('%02d:59:59', (int) $hour);
                $q->whereBetween('start_time', [$start, $end]);
            })
            ->when($filters['member'] ?? null, function ($q, $term) {
                $q->whereHas('member', function ($member) use ($term) {
                    $member->search($term);
                });
            })
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    public function summary(array $filters): array
    {
        $rows = $this->query($filters)->get();
        $active = $rows->where('booking_status', '!=', BookingStatus::Cancelled);

        return [
            'total_bookings' => $rows->count(),
            'fully_paid' => $rows->where('payment_status', PaymentStatus::FullyPaid)->where('booking_status', '!=', BookingStatus::Cancelled)->count(),
            'partial_paid' => $rows->where('payment_status', PaymentStatus::PartialPaid)->where('booking_status', '!=', BookingStatus::Cancelled)->count(),
            'pending' => $rows->where('payment_status', PaymentStatus::Pending)->where('booking_status', '!=', BookingStatus::Cancelled)->count(),
            'cancelled' => $rows->where('booking_status', BookingStatus::Cancelled)->count(),
            'on_hold' => $rows->where('booking_status', BookingStatus::OnHold)->count(),
            'total_revenue' => $active->sum('total_amount'),
            'collected' => $active->sum('paid_amount'),
            'outstanding' => $active->sum('remaining_amount'),
        ];
    }

    public function charts(array $filters): array
    {
        $rows = $this->query($filters)->get();
        $active = $rows->where('booking_status', '!=', BookingStatus::Cancelled);

        $bySport = $active->groupBy(fn ($b) => $b->sport?->name ?? 'Unknown')
            ->map->count()
            ->sortDesc();

        $byDay = $active->groupBy(fn ($b) => $b->booking_date->format('D d M'))
            ->map->count();

        $revenueByMonth = $active->groupBy(fn ($b) => $b->booking_date->format('M Y'))
            ->map(fn ($group) => $group->sum('paid_amount'));

        $paymentDist = [
            'Fully Paid' => $active->where('payment_status', PaymentStatus::FullyPaid)->count(),
            'Partial Paid' => $active->where('payment_status', PaymentStatus::PartialPaid)->count(),
            'Pending' => $active->where('payment_status', PaymentStatus::Pending)->count(),
        ];

        $peak = $active->groupBy(fn ($b) => substr($b->startLabel(), 0, 2).':00')
            ->map->count()
            ->sortDesc();

        $utilization = $this->utilization($filters, $active);

        return [
            'by_sport' => $bySport->all(),
            'by_day' => $byDay->all(),
            'revenue_by_month' => $revenueByMonth->all(),
            'payment_dist' => $paymentDist,
            'peak_hours' => $peak->all(),
            'utilization' => $utilization,
        ];
    }

    public function exportRows(array $filters): array
    {
        return $this->query($filters)->get()->map(fn (Booking $booking) => [
            $booking->booking_number,
            $booking->booking_date->toDateString(),
            $booking->booking_date->format('l'),
            $booking->sport?->name,
            $booking->court?->name,
            $booking->startLabel(),
            $booking->endLabel(),
            $booking->duration.' min',
            $booking->member?->member_number,
            $booking->member?->name,
            $booking->member?->phone,
            $booking->total_amount,
            $booking->paid_amount,
            $booking->remaining_amount,
            $booking->payment_status?->label(),
            $booking->combinedLabel(),
            $booking->creator?->name,
            $booking->created_at?->toDateTimeString(),
        ])->all();
    }

    public function headers(): array
    {
        return [
            'Booking ID', 'Date', 'Day', 'Sport', 'Court', 'Start Time', 'End Time',
            'Duration', 'Member Number', 'Member Name', 'Phone', 'Total Amount',
            'Paid Amount', 'Remaining Amount', 'Payment Status', 'Booking Status',
            'Created By', 'Created Date',
        ];
    }

    public function dateRange(array $input): array
    {
        return [
            $input['from'] ?? now()->startOfMonth()->toDateString(),
            $input['to'] ?? now()->toDateString(),
        ];
    }

    private function utilization(array $filters, $active): array
    {
        $from = Carbon::parse($filters['from']);
        $to = Carbon::parse($filters['to']);
        $days = max(1, $from->diffInDays($to) + 1);

        return Court::query()
            ->with('sport')
            ->when($filters['sport_id'] ?? null, fn ($q, $id) => $q->where('sport_id', $id))
            ->when($filters['court_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->get()
            ->map(function (Court $court) use ($active, $days) {
                $available = max(1, TimeSlots::durationMinutes(
                    TimeSlots::label((string) $court->opening_time),
                    TimeSlots::label((string) $court->closing_time),
                ) * $days);
                $used = $active->where('court_id', $court->id)->sum('duration');

                return [
                    'name' => $court->name,
                    'sport' => $court->sport?->name,
                    'percent' => min(100, round(($used / $available) * 100)),
                ];
            })
            ->sortByDesc('percent')
            ->values()
            ->all();
    }

    public function moneySummary(array $summary): array
    {
        return collect($summary)->map(function ($value, $key) {
            if (in_array($key, ['total_revenue', 'collected', 'outstanding'], true)) {
                return Money::format($value);
            }

            return $value;
        })->all();
    }
}
