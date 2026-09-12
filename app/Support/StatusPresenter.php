<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;

class StatusPresenter
{
    public static function combined(?BookingStatus $booking, ?PaymentStatus $payment): string
    {
        if ($booking === BookingStatus::OnHold) {
            return 'On Hold';
        }

        if ($booking === BookingStatus::Cancelled) {
            return 'Cancelled';
        }

        if ($booking === BookingStatus::Completed) {
            return 'Completed'.($payment ? ' • '.$payment->label() : '');
        }

        return 'Booked • '.($payment?->label() ?? 'Pending');
    }

    public static function tone(?BookingStatus $booking, ?PaymentStatus $payment): string
    {
        if ($booking === BookingStatus::OnHold) {
            return 'hold';
        }

        if ($booking === BookingStatus::Cancelled) {
            return 'cancelled';
        }

        return match ($payment) {
            PaymentStatus::FullyPaid => 'paid',
            PaymentStatus::PartialPaid => 'partial',
            default => 'pending',
        };
    }
}
