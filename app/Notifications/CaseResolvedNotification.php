<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CaseResolvedNotification extends Notification
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

        return $this->caseAlertMail([
            'case' => $this->case,
            'subject' => sprintf('[%s] Case marked resolved', $this->case->case_number),
            'headline' => sprintf('Case %s marked resolved', $this->caseShortNumber($this->case)),
            'intro' => sprintf(
                'Case %s (%s) has been marked resolved by %s.',
                $this->case->case_number,
                $this->case->complainant_name,
                $department,
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return NotificationPayload::make(
            type: 'case_resolved',
            case: $this->case,
            title: 'Case resolved',
            message: sprintf(' was marked resolved by %s.', $this->case->department?->name ?? 'the AC office'),
            meta: sprintf('Closed in %d days', $this->case->daysOpen()),
        );
    }
}
