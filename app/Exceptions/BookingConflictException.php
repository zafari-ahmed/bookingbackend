<?php

namespace App\Exceptions;

use App\Models\Booking;
use Exception;

class BookingConflictException extends Exception
{
    public function __construct(
        string $message = 'This court is already booked for the selected time.',
        public readonly ?Booking $conflict = null,
    ) {
        parent::__construct($message);
    }
}
