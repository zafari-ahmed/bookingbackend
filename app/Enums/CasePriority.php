<?php

declare(strict_types=1);

namespace App\Enums;

enum CasePriority: string
{
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Normal => 'Routine handling within the standard 15-day window.',
            self::High => 'Flagged for departmental follow-up within 72 hours.',
            self::Urgent => 'Same-day attention required. The AC office is alerted directly.',
        };
    }

    /**
     * Segmented-control active state colours (design-system.md section 5).
     */
    public function pillClasses(): string
    {
        return match ($this) {
            self::Normal => 'bg-white text-text-secondary',
            self::High => 'bg-pending-bg text-pending-text',
            self::Urgent => 'bg-escalated-bg text-escalated-text',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Normal => 'bg-text-muted',
            self::High => 'bg-pending-text',
            self::Urgent => 'bg-escalated-text',
        };
    }

    public function textClass(): string
    {
        return match ($this) {
            self::Normal => 'text-text-muted',
            self::High => 'text-pending-text',
            self::Urgent => 'text-escalated-text',
        };
    }

    public function isElevated(): bool
    {
        return $this !== self::Normal;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $priority): array => [$priority->value => $priority->label()])
            ->all();
    }
}
