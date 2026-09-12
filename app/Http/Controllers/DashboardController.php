<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Setting;
use App\Models\Sport;
use App\Services\CalendarService;
use App\Support\TimeSlots;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private CalendarService $calendar) {}

    public function index()
    {
        $today = now()->toDateString();
        $tomorrow = now()->copy()->addDay()->toDateString();
        $bookings = Booking::query()
            ->with(['member', 'sport', 'court'])
            ->where(function ($q) use ($today, $tomorrow) {
                $q->whereDate('booking_date', $today)
                    ->orWhere(function ($morning) use ($tomorrow) {
                        $morning->whereDate('booking_date', $tomorrow)
                            ->where('start_time', '<', '12:00:00');
                    });
            })
            ->orderBy('start_time')
            ->get();

        $active = $bookings->where('booking_status', '!=', BookingStatus::Cancelled);
        $slotMinutes = Setting::slotDuration();
        $courts = Court::query()->where('status', 'active')->get();
        $available = 0;

        foreach ($courts as $court) {
            $slots = TimeSlots::generate(substr((string) $court->opening_time, 0, 5), substr((string) $court->closing_time, 0, 5), $slotMinutes);
            $used = $active->where('court_id', $court->id)->sum(fn ($b) => TimeSlots::span($b->startLabel(), $b->endLabel(), $slotMinutes));
            $available += max(0, count($slots) - $used);
        }

        $cards = [
            ['key' => 'today', 'label' => "Today's Bookings", 'value' => $bookings->count(), 'tone' => 'navy'],
            ['key' => 'paid', 'label' => 'Fully Paid', 'value' => $active->where('payment_status', PaymentStatus::FullyPaid)->count(), 'tone' => 'paid'],
            ['key' => 'partial', 'label' => 'Partially Paid', 'value' => $active->where('payment_status', PaymentStatus::PartialPaid)->count(), 'tone' => 'partial'],
            ['key' => 'pending', 'label' => 'Pending Payment', 'value' => $active->where('payment_status', PaymentStatus::Pending)->count(), 'tone' => 'pending'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => $bookings->where('booking_status', BookingStatus::Cancelled)->count(), 'tone' => 'cancelled'],
            ['key' => 'hold', 'label' => 'On Hold', 'value' => $bookings->where('booking_status', BookingStatus::OnHold)->count(), 'tone' => 'hold'],
            ['key' => 'available', 'label' => 'Available Slots', 'value' => $available, 'tone' => 'available'],
            ['key' => 'revenue', 'label' => "Today's Revenue", 'value' => 'Rs. '.number_format($active->sum('paid_amount'), 0), 'tone' => 'lime'],
        ];

        return view('dashboard.index', [
            'cards' => $cards,
            'todayLabel' => Carbon::today()->format('l, d F Y'),
            'date' => $today,
            'sports' => Sport::query()->active()->orderBy('sort_order')->get(),
            'courts' => Court::query()->with('sport')->active()->orderBy('name')->get(),
        ]);
    }
}
