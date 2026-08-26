<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseEscalatedNotification extends Notification
{
    use BuildsCaseAlertMail;
    use Queueable;

    public function __construct(public readonly CaseModel $case) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $department = $this->case->department?->name ?? 'the holding department';
        $window = (int) config('cases.overdue_after_days', 15);

        return $this->caseAlertMail([
            'case' => $this->case,
            'subject' => sprintf('[%s] Case escalated — action required', $this->case->case_number),
            'headline' => sprintf('Case %s needs your action', $this->caseShortNumber($this->case)),
            'intro' => sprintf(
                'A case referred to %s has passed the %d-day resolution window. Please record the action taken within 48 hours.',
                $department,
                $window,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return NotificationPayload::make(
            type: 'case_escalated',
            case: $this->case,
            title: 'Case escalated',
            message: sprintf(' was escalated by %s.', $this->case->department?->name ?? 'the AC office'),
            meta: sprintf('Open for %d days', $this->case->daysOpen()),
        );
    }
}
