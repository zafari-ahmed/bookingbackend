<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case FullyPaid = 'fully_paid';
    case PartialPaid = 'partial_paid';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::FullyPaid => 'Fully Paid',
            self::PartialPaid => 'Partial Paid',
            self::Pending => 'Pending',
        };
    }

    public static function fromAmounts(float $total, float $paid): self
    {
        if ($paid <= 0) {
            return self::Pending;
        }

        if ($paid >= $total) {
            return self::FullyPaid;
        }

        return self::PartialPaid;
    }
}
