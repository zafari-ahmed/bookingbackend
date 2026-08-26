<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Carbon;

enum ActivityPeriod: string
{
    case Recent = 'recent';
    case Week = 'week';
    case Month = 'month';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Recent => 'Recent',
            self::Week => 'Last week',
            self::Month => 'Last month',
            self::All => 'All',
        };
    }

    /**
     * Inclusive lower bound for the period, or null for an unbounded query.
     */
    public function since(): ?Carbon
    {
        return match ($this) {
            self::Recent => Carbon::now()->subDays(3),
            self::Week => Carbon::now()->subWeek(),
            self::Month => Carbon::now()->subMonth(),
            self::All => null,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function tabs(): array
    {
        return self::cases();
    }

    public static function fromQuery(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Recent;
    }
}
