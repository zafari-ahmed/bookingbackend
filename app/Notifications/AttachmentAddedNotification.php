<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttachmentAddedNotification extends Notification
{
    use BuildsCaseAlertMail;
    use Queueable;

    /**
     * @param  array<int, string>  $fileNames
     */
    public function __construct(
        public readonly CaseModel $case,
        public readonly array $fileNames,
        public readonly string $uploaderName,
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
            'subject' => sprintf('[%s] File added to the case', $this->case->case_number),
            'headline' => sprintf('File added to case %s', $this->caseShortNumber($this->case)),
            'intro' => sprintf(
                '%s uploaded %s on this case file. Open the portal to view or download it.',
                $this->uploaderName,
                $this->fileSummary(),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return NotificationPayload::make(
            type: 'attachment_added',
            case: $this->case,
            title: 'File added',
            message: sprintf(' — %s.', $this->fileSummary()),
            meta: implode(', ', $this->fileNames),
            prefix: sprintf('%s added a file on ', $this->uploaderName),
        );
    }

    private function fileSummary(): string
    {
        $count = count($this->fileNames);

        if ($count === 1) {
            return '"'.$this->fileNames[0].'"';
        }

        return $count.' files';
    }
}
