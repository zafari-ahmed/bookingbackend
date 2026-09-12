<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtRequest;
use App\Http\Requests\StoreSportRequest;
use App\Models\BookingHold;
use App\Models\BookingPayment;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Models\Sport;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SportController extends Controller
{
    public function __construct(private ActivityLogger $logger) {}

    public function index()
    {
        $sports = Sport::query()->with(['courts' => fn ($q) => $q->orderBy('sort_order')])->orderBy('sort_order')->orderBy('name')->get();

        return view('sports.index', compact('sports'));
    }

    public function store(StoreSportRequest $request)
    {
        $sport = Sport::query()->create($request->validated());
        $this->logger->log('sport.created', "{$request->user()->name} added sport {$sport->name}", $sport, $request->user());

        return back()->with('success', 'Sport added.');
    }

    public function update(StoreSportRequest $request, Sport $sport)
    {
        $sport->update($request->validated());
        $this->logger->log('sport.updated', "{$request->user()->name} updated sport {$sport->name}", $sport, $request->user());

        return back()->with('success', 'Sport updated.');
    }

    public function storeCourt(StoreCourtRequest $request)
    {
        $court = Court::query()->create($request->validated());
        $this->logger->log('court.created', "{$request->user()->name} added {$court->name}", $court, $request->user());

        return back()->with('success', 'Court added.');
    }

    public function updateCourt(StoreCourtRequest $request, Court $court)
    {
        $court->update($request->validated());
        $this->logger->log('court.updated', "{$request->user()->name} updated {$court->name}", $court, $request->user());

        return back()->with('success', 'Court updated.');
    }

    public function toggleCourt(Request $request, Court $court)
    {
        abort_unless($request->user()->canManageFacilities(), 403);
        $court->update(['status' => $court->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', $court->name.' is now '.$court->status.'.');
    }

    public function destroy(Request $request, Sport $sport)
    {
        abort_unless($request->user()->canManageFacilities(), 403);

        if ($sport->bookings()->exists()) {
            return back()->withErrors([
                'slot' => $sport->name.' has booking history, so it cannot be deleted. Disable it instead.',
            ]);
        }

        $name = $sport->name;
        $sport->courts->each(function (Court $court) {
            $court->holds()->delete();
            $court->blocks()->delete();
            $court->delete();
        });
        $sport->delete();
        $this->logger->log('sport.deleted', "{$request->user()->name} deleted sport {$name}", null, $request->user());

        return back()->with('success', $name.' was deleted.');
    }

    public function destroyCourt(Request $request, Court $court)
    {
        abort_unless($request->user()->canManageFacilities(), 403);

        $name = $court->name;

        DB::transaction(function () use ($court) {
            $bookingIds = $court->bookings()->pluck('id');
            if ($bookingIds->isNotEmpty()) {
                BookingPayment::query()->whereIn('booking_id', $bookingIds)->delete();
                BookingHold::query()->whereIn('booking_id', $bookingIds)->delete();
                $court->bookings()->delete();
            }

            BookingHold::query()->where('court_id', $court->id)->delete();
            CourtBlock::query()->where('court_id', $court->id)->delete();
            $court->delete();
        });

        $this->logger->log('court.deleted', "{$request->user()->name} deleted {$name}", null, $request->user());

        return back()->with('success', $name.' was deleted.');
    }
}
