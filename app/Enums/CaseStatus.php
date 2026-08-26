<?php

declare(strict_types=1);

namespace App\Enums;

enum CaseStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Escalated = 'escalated';
    case Referred = 'referred';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Escalated => 'Escalated',
            self::Referred => 'Referred',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    /**
     * Status pill background/text pairs from design-system.md section 1.
     */
    public function pillClasses(): string
    {
        return match ($this) {
            self::Pending, self::Assigned => 'bg-pending-bg text-pending-text',
            self::InProgress => 'bg-progress-bg text-progress-text',
            self::Escalated => 'bg-escalated-bg text-escalated-text',
            self::Referred => 'bg-referred-bg text-referred-text',
            self::Resolved => 'bg-resolved-bg text-resolved-text',
            self::Closed => 'bg-closed-bg text-closed-text',
        };
    }

    /**
     * Dot colour used by the activity timeline.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::Pending, self::Assigned => 'bg-pending-text',
            self::InProgress => 'bg-progress-text',
            self::Escalated => 'bg-escalated-text',
            self::Referred => 'bg-referred-text',
            self::Resolved => 'bg-resolved-text',
            self::Closed => 'bg-closed-text',
        };
    }

    /**
     * Inline hex pair for HTML emails, which cannot use Tailwind classes.
     *
     * @return array{background: string, text: string}
     */
    public function pillColors(): array
    {
        return match ($this) {
            self::Pending, self::Assigned => ['background' => '#FBEBD3', 'text' => '#9A6A1E'],
            self::InProgress => ['background' => '#EAE6F7', 'text' => '#5B4FA8'],
            self::Escalated => ['background' => '#FBE3E3', 'text' => '#C43D3D'],
            self::Referred => ['background' => '#DCE7F7', 'text' => '#2E5FA8'],
            self::Resolved => ['background' => '#E1EFE0', 'text' => '#3A6B39'],
            self::Closed => ['background' => '#EDEAE2', 'text' => '#6B6B6B'],
        };
    }

    /**
     * Statuses that still require departmental action.
     *
     * @return array<int, self>
     */
    public static function openStatuses(): array
    {
        return [self::Pending, self::Assigned, self::InProgress, self::Escalated, self::Referred];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::openStatuses(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Resolved || $this === self::Closed;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
