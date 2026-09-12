<?php

namespace App\Models;

use App\Enums\HoldStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_id', 'court_id', 'booking_date', 'start_time', 'end_time',
    'member_id', 'reason', 'expires_at', 'status', 'notes', 'created_by',
])]
class BookingHold extends Model
{
    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'expires_at' => 'datetime',
            'status' => HoldStatus::class,
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
