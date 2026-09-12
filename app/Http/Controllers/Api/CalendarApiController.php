<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalendarService;
use Illuminate\Http\Request;

class CalendarApiController extends Controller
{
    public function __invoke(Request $request, CalendarService $calendar)
    {
        $filters = $request->only(['sport_id', 'court_id', 'booking_status', 'payment_status']);
        $date = $request->query('date', now()->toDateString());
        $view = $request->query('view', 'day');

        $data = match ($view) {
            'week' => $calendar->week($date, $filters),
            'month' => $calendar->month($date, $filters),
            default => $calendar->day($date, $filters),
        };

        return response()->json($data);
    }
}
