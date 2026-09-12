<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Member;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['members' => [], 'bookings' => [], 'courts' => []]);
        }

        return response()->json([
            'members' => Member::query()->search($q)->limit(6)->get(['id', 'name', 'member_number', 'phone']),
            'bookings' => Booking::query()
                ->with(['member:id,name,member_number', 'court:id,name'])
                ->where('booking_number', 'like', "%{$q}%")
                ->limit(6)
                ->get()
                ->map(fn (Booking $b) => [
                    'id' => $b->id,
                    'booking_number' => $b->booking_number,
                    'date' => $b->booking_date->toDateString(),
                    'member' => $b->member?->name,
                    'court' => $b->court?->name,
                ]),
            'courts' => Court::query()->where('name', 'like', "%{$q}%")->limit(6)->get(['id', 'name', 'sport_id']),
        ]);
    }
}
