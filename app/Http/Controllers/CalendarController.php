<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Sport;
use App\Services\CalendarService;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(private CalendarService $calendar) {}

    public function index(Request $request)
    {
        return view('calendar.index', [
            'sports' => Sport::query()->active()->orderBy('sort_order')->get(),
            'courts' => Court::query()->with('sport')->active()->orderBy('name')->get(),
            'date' => $request->query('date', now()->toDateString()),
            'view' => $request->query('view', 'day'),
        ]);
    }
}
