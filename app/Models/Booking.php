<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Support\Money;
use App\Support\StatusPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'booking_number', 'member_id', 'sport_id', 'court_id', 'booking_date',
    'start_time', 'end_time', 'duration', 'players_count', 'total_amount',
    'paid_amount', 'remaining_amount', 'booking_status', 'payment_status',
    'payment_method', 'notes', 'created_by', 'cancelled_by', 'cancelled_at',
    'cancellation_reason',
])]
class Booking extends Model
{
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'total_amount' => 'float',
            'paid_amount' => 'float',
            'remaining_amount' => 'float',
            'booking_status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if (blank($booking->booking_number)) {
                $booking->booking_number = self::nextNumber();
            }
            $booking->syncPaymentFields();
        });

        static::updating(function (Booking $booking): void {
            $booking->syncPaymentFields();
        });
    }

    public static function nextNumber(): string
    {
        $last = self::query()->orderByDesc('id')->value('booking_number');
        $n = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 10001;

        return 'BK-'.$n;
    }

    public function syncPaymentFields(): void
    {
        $this->paid_amount = max(0, (float) $this->paid_amount);
        $this->total_amount = max(0, (float) $this->total_amount);
        if ($this->paid_amount > $this->total_amount) {
            $this->paid_amount = $this->total_amount;
        }
        $this->remaining_amount = Money::remaining($this->total_amount, $this->paid_amount);
        $this->payment_status = PaymentStatus::fromAmounts($this->total_amount, $this->paid_amount);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class)->latest('payment_date');
    }

    public function hold(): HasOne
    {
        return $this->hasOne(BookingHold::class)->latestOfMany();
    }

    public function scopeActiveOccupancy(Builder $query): Builder
    {
        return $query->whereNotIn('booking_status', [BookingStatus::Cancelled->value]);
    }

    public function scopeOverlapping(Builder $query, int $courtId, string $date, string $start, string $end, ?int $ignoreId = null): Builder
    {
        return $query->where('court_id', $courtId)
            ->whereDate('booking_date', $date)
            ->activeOccupancy()
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    public function combinedLabel(): string
    {
        return StatusPresenter::combined($this->booking_status, $this->payment_status);
    }

    public function tone(): string
    {
        return StatusPresenter::tone($this->booking_status, $this->payment_status);
    }

    public function startLabel(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endLabel(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }

    public function latestPayment()
    {
        return $this->payments()->first();
    }
}
