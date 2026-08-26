<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case Created = 'created';
    case Assigned = 'assigned';
    case Forwarded = 'forwarded';
    case Returned = 'returned';
    case CommentAdded = 'comment_added';
    case AttachmentAdded = 'attachment_added';
    case AttachmentViewed = 'attachment_viewed';
    case StatusChanged = 'status_changed';
    case PriorityChanged = 'priority_changed';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Case created',
            self::Assigned => 'Assigned',
            self::Forwarded => 'Forwarded',
            self::Returned => 'Returned',
            self::CommentAdded => 'Remark added',
            self::AttachmentAdded => 'Attachment added',
            self::AttachmentViewed => 'Attachment viewed',
            self::StatusChanged => 'Status changed',
            self::PriorityChanged => 'Priority changed',
            self::Escalated => 'Escalated',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Reopened => 'Reopened',
        };
    }

    /**
     * Timeline dot colour (design-system.md section 5 — activity timeline).
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::Created => 'bg-teal',
            self::Assigned, self::Forwarded, self::Returned => 'bg-referred-text',
            self::Escalated => 'bg-escalated-text',
            self::Resolved => 'bg-resolved-text',
            self::Closed => 'bg-closed-text',
            self::PriorityChanged, self::StatusChanged => 'bg-pending-text',
            self::Reopened => 'bg-progress-text',
            self::CommentAdded, self::AttachmentAdded, self::AttachmentViewed => 'bg-text-muted',
        };
    }

    public function pillClasses(): string
    {
        return match ($this) {
            self::Created => 'bg-teal-tint text-teal',
            self::Assigned, self::Forwarded, self::Returned => 'bg-referred-bg text-referred-text',
            self::Escalated => 'bg-escalated-bg text-escalated-text',
            self::Resolved => 'bg-resolved-bg text-resolved-text',
            self::Closed => 'bg-closed-bg text-closed-text',
            self::PriorityChanged, self::StatusChanged => 'bg-pending-bg text-pending-text',
            self::Reopened => 'bg-progress-bg text-progress-text',
            self::CommentAdded, self::AttachmentAdded, self::AttachmentViewed => 'bg-closed-bg text-text-muted',
        };
    }
}
