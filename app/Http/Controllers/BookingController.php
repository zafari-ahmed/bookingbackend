<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingConflictException;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Court;
use App\Models\Sport;
use App\Services\BookingService;
use App\Services\CalendarService;
use App\Support\TimeSlots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookings,
        private CalendarService $calendar,
    ) {}

    public function create(Request $request)
    {
        return view('bookings.create', [
            'sports' => Sport::query()->active()->with(['courts' => fn ($q) => $q->active()])->orderBy('sort_order')->get(),
            'prefill' => [
                'date' => $request->query('date', now()->toDateString()),
                'sport_id' => $request->query('sport_id'),
                'court_id' => $request->query('court_id'),
                'start_time' => $request->query('start_time'),
            ],
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        try {
            $booking = $this->bookings->create($request->validated(), $request->user());
        } catch (BookingConflictException $e) {
            return $this->conflictResponse($request, $e);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Booking confirmed.',
                'booking' => $this->calendar->serializeBooking($booking->load(['member', 'sport', 'court', 'creator', 'hold'])),
            ]);
        }

        return redirect()->route('calendar.index', ['date' => $booking->booking_date->toDateString()])
            ->with('success', "Booking {$booking->booking_number} confirmed.");
    }

    public function show(Booking $booking)
    {
        $booking->load(['member', 'sport', 'court', 'creator', 'canceller', 'payments.receiver', 'hold']);

        return response()->json([
            'booking' => $this->calendar->serializeBooking($booking),
            'payments' => $this->paymentRows($booking),
        ]);
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        try {
            $booking = $this->bookings->update($booking, $request->validated(), $request->user());
        } catch (BookingConflictException $e) {
            return $this->conflictResponse($request, $e);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'message' => 'Booking updated.',
            'booking' => $this->calendar->serializeBooking($booking),
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless($request->user()->canCancelBookings(), 403);

        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $booking = $this->bookings->cancel($booking, $request->user(), $request->input('reason'));

        return response()->json([
            'ok' => true,
            'message' => 'Booking cancelled.',
            'booking' => $this->calendar->serializeBooking($booking),
        ]);
    }

    public function hold(Request $request)
    {
        abort_unless($request->user()->canManageHolds(), 403);

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'sport_id' => ['required', 'exists:sports,id'],
            'court_id' => ['required', 'exists:courts,id'],
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $booking = $this->bookings->hold($data, $request->user());
        } catch (BookingConflictException $e) {
            return $this->conflictResponse($request, $e);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Slot placed on hold.',
            'booking' => $this->calendar->serializeBooking($booking),
        ]);
    }

    public function release(Request $request, Booking $booking)
    {
        abort_unless($request->user()->canManageHolds(), 403);
        $this->bookings->releaseHold($booking, $request->user());

        return response()->json(['ok' => true, 'message' => 'Hold released. Slot is available again.']);
    }

    public function payment(Request $request, Booking $booking)
    {
        $request->merge([
            'payment_method' => $request->input('payment_method') ?: $request->input('method'),
        ]);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof' => ['nullable', 'image', 'max:4096'],
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs/'.$booking->id, 'public');
        }

        $booking = $this->bookings->addPayment(
            $booking,
            (float) $data['amount'],
            $data['payment_method'],
            $request->user(),
            $data['notes'] ?? null,
            $proofPath,
        );

        return response()->json([
            'ok' => true,
            'message' => 'Payment recorded.',
            'booking' => $this->calendar->serializeBooking($booking),
            'payments' => $this->paymentRows($booking->load('payments.receiver')),
        ]);
    }

    public function destroyPayment(Request $request, Booking $booking, BookingPayment $payment)
    {
        try {
            $booking = $this->bookings->deletePayment($booking, $payment, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($request, $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'message' => 'Payment removed.',
            'booking' => $this->calendar->serializeBooking($booking),
            'payments' => $this->paymentRows($booking->load('payments.receiver')),
        ]);
    }

    public function quote(Request $request)
    {
        $request->merge([
            'start_time' => substr((string) $request->input('start_time'), 0, 5),
            'end_time' => substr((string) $request->input('end_time'), 0, 5),
        ]);

        $data = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        $court = Court::query()->findOrFail($data['court_id']);
        $minutes = TimeSlots::durationMinutes($data['start_time'], $data['end_time']);

        return response()->json([
            'amount' => $this->bookings->suggestedAmount($court, $data['start_time'], $data['end_time']),
            'minutes' => $minutes,
            'hours' => round($minutes / 60, 2),
        ]);
    }

    private function conflictResponse(Request $request, BookingConflictException $e)
    {
        $payload = [
            'ok' => false,
            'conflict' => true,
            'message' => $e->getMessage(),
            'booking' => $e->conflict ? $this->calendar->serializeBooking($e->conflict) : null,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload, 409);
        }

        return back()->withErrors(['slot' => $e->getMessage()])->withInput();
    }

    private function paymentRows(Booking $booking)
    {
        return $booking->payments->map(fn ($p) => [
            'id' => $p->id,
            'amount' => $p->amount,
            'payment_method' => $p->payment_method,
            'payment_date' => $p->payment_date?->toDateTimeString(),
            'received_by' => $p->receiver?->name,
            'notes' => $p->notes,
            'proof_url' => $p->proof_path ? Storage::disk('public')->url($p->proof_path) : null,
        ]);
    }

    private function errorResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return back()->withErrors(['slot' => $message])->withInput();
    }
}
