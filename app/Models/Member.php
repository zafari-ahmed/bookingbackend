<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['member_number', 'name', 'phone', 'email', 'gender', 'dob', 'notes', 'status'])]
class Member extends Model
{
    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member): void {
            if (blank($member->member_number)) {
                $member->member_number = self::nextNumber();
            }
        });
    }

    public static function nextNumber(): string
    {
        $last = self::query()->orderByDesc('id')->value('member_number');
        $n = $last ? ((int) preg_replace('/\D/', '', $last)) + 1 : 1001;

        return 'M-'.$n;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('member_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function totalBookings(): int
    {
        return $this->bookings()->count();
    }

    public function completedBookings(): int
    {
        return $this->bookings()->whereIn('booking_status', [
            BookingStatus::Confirmed->value,
            BookingStatus::Completed->value,
        ])->count();
    }

    public function cancelledBookings(): int
    {
        return $this->bookings()->where('booking_status', BookingStatus::Cancelled->value)->count();
    }

    public function totalSpent(): float
    {
        return (float) $this->bookings()
            ->where('booking_status', '!=', BookingStatus::Cancelled->value)
            ->sum('paid_amount');
    }

    public function outstandingAmount(): float
    {
        return (float) $this->bookings()
            ->where('booking_status', '!=', BookingStatus::Cancelled->value)
            ->sum('remaining_amount');
    }

    public function lastBookingDate(): ?string
    {
        return optional($this->bookings()->latest('booking_date')->first())->booking_date?->toDateString();
    }
}
