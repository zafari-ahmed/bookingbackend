<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseModel;
use App\Models\Department;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseAssignedNotification extends Notification
{
    use BuildsCaseAlertMail;
    use Queueable;

    public function __construct(
        public readonly CaseModel $case,
        public readonly Department $department,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->caseAlertMail([
            'case' => $this->case,
            'subject' => sprintf('[%s] Case assigned to %s', $this->case->case_number, $this->department->name),
            'headline' => sprintf('Case %s assigned to %s', $this->caseShortNumber($this->case), $this->department->name),
            'intro' => sprintf(
                'A new case for %s has been assigned to %s. Please review the file and record the action taken.',
                $this->case->complainant_name,
                $this->department->name,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return NotificationPayload::make(
            type: 'case_assigned',
            case: $this->case,
            title: 'Case assigned',
            message: sprintf(' was assigned to %s.', $this->department->name),
            meta: sprintf('%s · %s', $this->department->name, $this->case->complainant_name),
        );
    }
}
