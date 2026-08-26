<?php

declare(strict_types=1);

namespace App\Enums;

enum RoutingAction: string
{
    case Assigned = 'assigned';
    case Forwarded = 'forwarded';
    case Returned = 'returned';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::Forwarded => 'Forwarded',
            self::Returned => 'Returned',
            self::Completed => 'Completed',
        };
    }

    public function toActivityType(): ActivityType
    {
        return match ($this) {
            self::Assigned => ActivityType::Assigned,
            self::Forwarded => ActivityType::Forwarded,
            self::Returned => ActivityType::Returned,
            self::Completed => ActivityType::Resolved,
        };
    }
}
