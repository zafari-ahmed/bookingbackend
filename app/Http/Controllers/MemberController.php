<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Models\Member;
use App\Models\Sport;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private ActivityLogger $logger) {}

    public function index(Request $request)
    {
        $members = Member::query()
            ->search($request->query('q'))
            ->withCount('bookings')
            ->latest()
            ->paginate(16)
            ->withQueryString();

        $members->getCollection()->transform(function (Member $member) {
            $member->setAttribute('total_spent', $member->totalSpent());
            $member->setAttribute('last_booking', $member->lastBookingDate());

            return $member;
        });

        return view('members.index', [
            'members' => $members,
            'q' => $request->query('q'),
        ]);
    }

    public function show(Request $request, Member $member)
    {
        $history = $member->bookings()
            ->with(['sport', 'court'])
            ->when($request->query('sport_id'), fn ($q, $id) => $q->where('sport_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('booking_status', $status))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('booking_date', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('booking_date', '<=', $to))
            ->latest('booking_date')
            ->latest('start_time')
            ->paginate(12)
            ->withQueryString();

        return view('members.show', [
            'member' => $member,
            'history' => $history,
            'sports' => Sport::query()->orderBy('name')->get(),
            'stats' => [
                'total' => $member->totalBookings(),
                'completed' => $member->completedBookings(),
                'cancelled' => $member->cancelledBookings(),
                'spent' => $member->totalSpent(),
                'outstanding' => $member->outstandingAmount(),
            ],
        ]);
    }

    public function store(StoreMemberRequest $request)
    {
        $member = Member::query()->create($request->validated());
        $this->logger->log('member.created', "{$request->user()->name} created member {$member->member_number}", $member, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'member' => $member]);
        }

        return redirect()->route('members.show', $member)->with('success', 'Member created.');
    }

    public function update(StoreMemberRequest $request, Member $member)
    {
        $member->update($request->validated());
        $this->logger->log('member.updated', "{$request->user()->name} updated member {$member->member_number}", $member, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'member' => $member]);
        }

        return back()->with('success', 'Member details saved.');
    }

    public function destroy(Request $request, Member $member)
    {
        abort_unless($request->user()->isAdmin(), 403);

        if ($member->bookings()->exists()) {
            $member->update(['status' => 'inactive']);

            return back()->with('success', 'Member has booking history, so the profile was disabled instead of deleted.');
        }

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member deleted.');
    }
}
