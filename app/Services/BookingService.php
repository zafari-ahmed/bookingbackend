<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\BookingHold;
use App\Models\BookingPayment;
use App\Models\Court;
use App\Models\User;
use App\Support\Money;
use App\Support\TimeSlots;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class BookingService
{
    public function __construct(
        private ActivityLogger $logger,
        private NotificationService $notifications,
    ) {}

    public function create(array $data, User $actor): Booking
    {
        $data = $this->normalizeBookingWindow($data);

        return DB::transaction(function () use ($data, $actor) {
            $this->lockCourt((int) $data['court_id']);
            $conflict = $this->findConflict(
                (int) $data['court_id'],
                $data['booking_date'],
                $data['start_time'],
                $data['end_time'],
            );

            if ($conflict) {
                throw new BookingConflictException(
                    'This court is already booked for the selected time.',
                    $conflict,
                );
            }

            $amounts = $this->normalizeAmounts($data);

            $booking = Booking::query()->create([
                'member_id' => $data['member_id'],
                'sport_id' => $data['sport_id'],
                'court_id' => $data['court_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration' => TimeSlots::durationMinutes($data['start_time'], $data['end_time']),
                'players_count' => $data['players_count'] ?? 2,
                'total_amount' => $amounts['total'],
                'paid_amount' => $amounts['paid'],
                'booking_status' => BookingStatus::Confirmed,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            if ($amounts['paid'] > 0) {
                $this->recordPayment($booking, $amounts['paid'], $data['payment_method'] ?? 'cash', $actor, $data['payment_notes'] ?? null);
            }

            $booking->load(['member', 'sport', 'court', 'creator']);
            $this->logger->log('booking.created', "{$actor->name} created booking {$booking->booking_number}", $booking, $actor);
            $this->notifications->bookingCreated($booking);

            return $booking;
        });
    }

    public function update(Booking $booking, array $data, User $actor): Booking
    {
        if ($booking->booking_status === BookingStatus::Cancelled) {
            throw new InvalidArgumentException('Cancelled bookings cannot be edited.');
        }

        $data = $this->normalizeBookingWindow($data);

        return DB::transaction(function () use ($booking, $data, $actor) {
            $this->lockCourt((int) $data['court_id']);
            $conflict = $this->findConflict(
                (int) $data['court_id'],
                $data['booking_date'],
                $data['start_time'],
                $data['end_time'],
                $booking->id,
            );

            if ($conflict) {
                throw new BookingConflictException(
                    'This court is already booked for the selected time.',
                    $conflict,
                );
            }

            $previousPayment = $booking->payment_status;
            $previousPaid = (float) $booking->paid_amount;
            $amounts = $this->normalizeAmounts($data);

            $booking->update([
                'member_id' => $data['member_id'],
                'sport_id' => $data['sport_id'],
                'court_id' => $data['court_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration' => TimeSlots::durationMinutes($data['start_time'], $data['end_time']),
                'players_count' => $data['players_count'] ?? $booking->players_count,
                'total_amount' => $amounts['total'],
                'paid_amount' => $amounts['paid'],
                'payment_method' => $data['payment_method'] ?? $booking->payment_method,
                'notes' => $data['notes'] ?? $booking->notes,
                'booking_status' => $booking->booking_status === BookingStatus::OnHold
                    ? BookingStatus::Confirmed
                    : $booking->booking_status,
            ]);

            if ($amounts['paid'] > $previousPaid) {
                $this->recordPayment($booking, $amounts['paid'] - $previousPaid, $data['payment_method'] ?? 'cash', $actor);
            }

            $booking->refresh()->load(['member', 'sport', 'court', 'creator']);

            if ($previousPayment !== $booking->payment_status) {
                $this->logger->log(
                    'booking.payment',
                    "{$actor->name} changed payment from {$previousPayment?->label()} to {$booking->payment_status->label()} on {$booking->booking_number}",
                    $booking,
                    $actor,
                );
            }

            $this->logger->log('booking.updated', "{$actor->name} updated booking {$booking->booking_number}", $booking, $actor);

            return $booking;
        });
    }

    public function cancel(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        if ($booking->booking_status === BookingStatus::Cancelled) {
            return $booking;
        }

        $booking->update([
            'booking_status' => BookingStatus::Cancelled,
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        if ($booking->hold) {
            $booking->hold->update(['status' => HoldStatus::Released]);
        }

        $this->logger->log('booking.cancelled', "{$actor->name} cancelled booking {$booking->booking_number}", $booking, $actor);
        $this->notifications->bookingCancelled($booking);

        return $booking->fresh(['member', 'sport', 'court', 'canceller']);
    }

    public function hold(array $data, User $actor): Booking
    {
        $data = $this->normalizeBookingWindow($data);

        return DB::transaction(function () use ($data, $actor) {
            $this->lockCourt((int) $data['court_id']);
            $conflict = $this->findConflict(
                (int) $data['court_id'],
                $data['booking_date'],
                $data['start_time'],
                $data['end_time'],
            );

            if ($conflict) {
                throw new BookingConflictException(
                    'This court is already booked for the selected time.',
                    $conflict,
                );
            }

            $booking = Booking::query()->create([
                'member_id' => $data['member_id'],
                'sport_id' => $data['sport_id'],
                'court_id' => $data['court_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'duration' => TimeSlots::durationMinutes($data['start_time'], $data['end_time']),
                'players_count' => $data['players_count'] ?? 2,
                'total_amount' => $data['total_amount'] ?? 0,
                'paid_amount' => 0,
                'booking_status' => BookingStatus::OnHold,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            BookingHold::query()->create([
                'booking_id' => $booking->id,
                'court_id' => $booking->court_id,
                'booking_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'member_id' => $booking->member_id,
                'reason' => $data['reason'] ?? 'Waiting for member confirmation',
                'expires_at' => $data['expires_at'] ?? now()->addHours(2),
                'status' => HoldStatus::Active,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $booking->load(['member', 'sport', 'court', 'hold']);
            $this->logger->log('booking.hold', "{$actor->name} placed {$booking->court->name} on hold", $booking, $actor);
            $this->notifications->holdPlaced($booking);

            return $booking;
        });
    }

    public function convertHold(Booking $booking, array $data, User $actor): Booking
    {
        if ($booking->booking_status !== BookingStatus::OnHold) {
            throw new InvalidArgumentException('Only held slots can be converted.');
        }

        $payload = array_merge([
            'member_id' => $booking->member_id,
            'sport_id' => $booking->sport_id,
            'court_id' => $booking->court_id,
            'booking_date' => $booking->booking_date->toDateString(),
            'start_time' => $booking->startLabel(),
            'end_time' => $booking->endLabel(),
            'players_count' => $booking->players_count,
            'notes' => $booking->notes,
        ], $data);

        $updated = $this->update($booking, $payload, $actor);
        $updated->hold?->update(['status' => HoldStatus::Converted]);

        return $updated;
    }

    public function releaseHold(Booking $booking, User $actor): void
    {
        DB::transaction(function () use ($booking, $actor) {
            $booking->hold?->update(['status' => HoldStatus::Released]);
            $booking->delete();
            $this->logger->log('booking.hold_released', "{$actor->name} released a hold on court #{$booking->court_id}", $booking, $actor);
        });
    }

    public function addPayment(Booking $booking, float $amount, string $method, User $actor, ?string $notes = null, ?string $proofPath = null): Booking
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $nextPaid = min($booking->total_amount, $booking->paid_amount + $amount);
        $applied = $nextPaid - $booking->paid_amount;

        if ($applied <= 0) {
            return $booking;
        }

        $this->recordPayment($booking, $applied, $method, $actor, $notes, $proofPath);

        $wasOnHold = $booking->booking_status === BookingStatus::OnHold;
        $payload = [
            'paid_amount' => $nextPaid,
            'payment_method' => $method,
        ];

        if ($wasOnHold) {
            $payload['booking_status'] = BookingStatus::Confirmed;
        }

        $booking->update($payload);

        if ($wasOnHold) {
            $booking->hold?->update(['status' => HoldStatus::Converted]);
            $this->logger->log(
                'booking.hold_converted',
                "{$actor->name} released the hold on {$booking->booking_number} after payment",
                $booking,
                $actor,
            );
        }

        $this->logger->log(
            'booking.payment',
            "{$actor->name} recorded ".Money::format($applied)." on {$booking->booking_number}",
            $booking,
            $actor,
        );

        return $booking->fresh(['member', 'sport', 'court', 'payments', 'hold']);
    }

    public function deletePayment(Booking $booking, BookingPayment $payment, User $actor): Booking
    {
        if ((int) $payment->booking_id !== (int) $booking->id) {
            throw new InvalidArgumentException('This payment does not belong to the selected booking.');
        }

        if ($payment->proof_path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->delete();

        $remainingPayments = $booking->payments()->get();
        $paid = min($booking->total_amount, (float) $remainingPayments->sum('amount'));
        $latest = $remainingPayments->first();

        $booking->update([
            'paid_amount' => $paid,
            'payment_method' => $latest?->payment_method,
        ]);

        $this->logger->log(
            'booking.payment_deleted',
            "{$actor->name} deleted a payment of ".Money::format($payment->amount)." on {$booking->booking_number}",
            $booking,
            $actor,
        );

        return $booking->fresh(['member', 'sport', 'court', 'payments', 'hold']);
    }

    public function suggestedAmount(Court $court, string $start, string $end): float
    {
        $hours = max(0.5, TimeSlots::durationMinutes($start, $end) / 60);

        return round($court->price_per_hour * $hours, 0);
    }

    public function findConflict(int $courtId, string $date, string $start, string $end, ?int $ignoreId = null): ?Booking
    {
        $range = TimeSlots::dateRange($date, $start, $end);
        $from = $range[0]->copy()->subDay()->toDateString();
        $to = $range[1]->toDateString();

        return Booking::query()
            ->with(['member', 'court', 'sport'])
            ->where('court_id', $courtId)
            ->activeOccupancy()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('booking_date', '>=', $from)
            ->whereDate('booking_date', '<=', $to)
            ->get()
            ->first(fn (Booking $booking) => TimeSlots::rangesOverlap(
                TimeSlots::dateRange($booking->booking_date->toDateString(), $booking->startLabel(), $booking->endLabel()),
                $range,
            ));
    }

    private function recordPayment(Booking $booking, float $amount, string $method, User $actor, ?string $notes = null, ?string $proofPath = null): void
    {
        BookingPayment::query()->create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'payment_method' => $method,
            'payment_date' => now(),
            'received_by' => $actor->id,
            'notes' => $notes,
            'proof_path' => $proofPath,
        ]);
    }

    private function lockCourt(int $courtId): void
    {
        Court::query()->where('id', $courtId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeBookingWindow(array $data): array
    {
        $court = Court::query()->findOrFail($data['court_id']);
        if ((int) $court->sport_id !== (int) $data['sport_id']) {
            throw new InvalidArgumentException('Selected court does not belong to the chosen sport.');
        }

        $open = TimeSlots::label((string) $court->opening_time);
        $close = TimeSlots::label((string) $court->closing_time);
        $start = TimeSlots::label((string) $data['start_time']);
        $end = TimeSlots::label((string) $data['end_time']);

        if ($start === $end) {
            throw new InvalidArgumentException('End time must be after start time.');
        }

        if (TimeSlots::durationMinutes($start, $end) < 30) {
            throw new InvalidArgumentException('Bookings must be at least 30 minutes.');
        }

        if (! TimeSlots::inHours($start, $open, $close) || ! TimeSlots::inHours($end, $open, $close, true)) {
            throw new InvalidArgumentException('Selected time is outside this court’s opening hours.');
        }

        $data['start_time'] = $start;
        $data['end_time'] = $end;
        $data['booking_date'] = TimeSlots::clockDate((string) $data['booking_date'], $start, $open, $close);

        return $data;
    }

    /**
     * @return array{total: float, paid: float}
     */
    private function normalizeAmounts(array $data): array
    {
        $total = max(0, (float) ($data['total_amount'] ?? 0));
        $paid = max(0, (float) ($data['paid_amount'] ?? 0));

        if ($paid > $total) {
            $paid = $total;
        }

        return ['total' => $total, 'paid' => $paid];
    }
}
