<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Booked',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
