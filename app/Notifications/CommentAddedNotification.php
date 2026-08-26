<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentAddedNotification extends Notification
{
    use BuildsCaseAlertMail;
    use Queueable;

    public function __construct(public readonly CaseComment $comment)
    {
        // Loaded up front so neither channel triggers a lazy load once the
        // notification has been queued and rehydrated.
        $comment->loadMissing(['case', 'user', 'department']);
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $case = $this->comment->case;
        $officer = $this->comment->user?->name ?? 'An officer';
        $department = $this->comment->department?->name ?? 'their department';

        return $this->caseAlertMail([
            'case' => $case,
            'subject' => sprintf('[%s] New remark on the case file', $case->case_number),
            'headline' => sprintf('New remark on case %s', $this->caseShortNumber($case)),
            'intro' => sprintf(
                '%s (%s) added a remark: “%s”',
                $officer,
                $department,
                str($this->comment->comment)->limit(180),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return NotificationPayload::make(
            type: 'comment_added',
            case: $this->comment->case,
            title: 'Remark added',
            message: '.',
            meta: str($this->comment->comment)->limit(90)->toString(),
            prefix: sprintf(
                '%s added a remark on ',
                $this->comment->user?->name ?? 'An officer',
            ),
        );
    }
}
