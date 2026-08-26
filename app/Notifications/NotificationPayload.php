<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseModel;

/**
 * Shared shape for the `data` column of every database notification.
 *
 * The Notifications page filters on `group` and renders `prefix`/`case_number`/
 * `message`, so keeping the shape in one place stops the list view from having
 * to special-case each notification class.
 */
final class NotificationPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(
        string $type,
        CaseModel $case,
        string $title,
        string $message,
        string $meta = '',
        string $prefix = '',
    ): array {
        return [
            'type' => $type,
            'group' => self::groupFor($type),
            'case_id' => $case->getKey(),
            'case_number' => $case->case_number,
            'title' => $title,
            'prefix' => $prefix,
            'message' => $message,
            'meta' => $meta,
        ];
    }

    /**
     * Maps a notification type onto one of the Notifications page filter tabs.
     */
    public static function groupFor(string $type): string
    {
        return match ($type) {
            'case_assigned', 'case_forwarded', 'case_created' => 'assignments',
            'comment_added', 'attachment_added', 'case_resolved' => 'comments',
            'case_escalated' => 'escalations',
            default => 'assignments',
        };
    }

    /**
     * Icon glyph and design-system colour pair for the row avatar.
     *
     * @return array{glyph: string, classes: string}
     */
    public static function badgeFor(string $type): array
    {
        return match ($type) {
            'comment_added' => ['glyph' => '✎', 'classes' => 'bg-progress-bg text-progress-text'],
            'attachment_added' => ['glyph' => '▤', 'classes' => 'bg-teal-tint text-teal'],
            'case_escalated' => ['glyph' => '▲', 'classes' => 'bg-escalated-bg text-escalated-text'],
            'case_resolved' => ['glyph' => '✓', 'classes' => 'bg-resolved-bg text-resolved-text'],
            'case_forwarded' => ['glyph' => '→', 'classes' => 'bg-referred-bg text-referred-text'],
            default => ['glyph' => '▤', 'classes' => 'bg-referred-bg text-referred-text'],
        };
    }
}
