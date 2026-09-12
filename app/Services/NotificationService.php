<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\StaffNotification;

class NotificationService
{
    public function notify(string $type, string $title, string $message, array $data = [], ?int $userId = null): StaffNotification
    {
        return StaffNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function bookingCreated(Booking $booking): void
    {
        $booking->loadMissing('member', 'court');
        $this->notify(
            'new_booking',
            'New booking',
            "{$booking->member->name} booked {$booking->court->name} at {$booking->startLabel()}.",
            ['booking_id' => $booking->id],
        );

        if ($booking->payment_status?->value === 'pending') {
            $this->notify(
                'payment_pending',
                'Payment pending',
                "Booking {$booking->booking_number} is awaiting payment.",
                ['booking_id' => $booking->id],
            );
        } elseif ($booking->payment_status?->value === 'partial_paid') {
            $this->notify(
                'partial_payment',
                'Partial payment',
                "Booking {$booking->booking_number} has a remaining balance of Rs. ".number_format($booking->remaining_amount, 0).'.',
                ['booking_id' => $booking->id],
            );
        }
    }

    public function bookingCancelled(Booking $booking): void
    {
        $booking->loadMissing('member', 'court');
        $this->notify(
            'booking_cancelled',
            'Booking cancelled',
            "{$booking->booking_number} for {$booking->member->name} was cancelled.",
            ['booking_id' => $booking->id],
        );
    }

    public function holdPlaced(Booking $booking): void
    {
        $booking->loadMissing('court');
        $this->notify(
            'hold_expiring',
            'Slot on hold',
            "{$booking->court->name} is on hold from {$booking->startLabel()} to {$booking->endLabel()}.",
            ['booking_id' => $booking->id],
        );
    }

    public function unread(?int $userId = null, int $limit = 12)
    {
        return StaffNotification::query()
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id');
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function unreadCount(?int $userId = null): int
    {
        return StaffNotification::query()
            ->whereNull('read_at')
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id');
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->count();
    }
}
