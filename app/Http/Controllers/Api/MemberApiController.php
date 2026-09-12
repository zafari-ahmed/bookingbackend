<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

class MemberApiController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['members' => []]);
        }

        $members = Member::query()
            ->search($q)
            ->limit(8)
            ->get()
            ->map(fn (Member $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'member_number' => $member->member_number,
                'phone' => $member->phone,
                'email' => $member->email,
                'total_bookings' => $member->totalBookings(),
                'last_booking' => $member->lastBookingDate(),
                'total_spent' => $member->totalSpent(),
            ]);

        return response()->json(['members' => $members]);
    }
}
