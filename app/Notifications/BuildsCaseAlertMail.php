<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CaseModel;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Shared HTML case-alert layout (navy header, status pill, case card, CTA).
 */
trait BuildsCaseAlertMail
{
    /**
     * @param  array{
     *     case: CaseModel,
     *     subject: string,
     *     headline: string,
     *     intro: string,
     *     badge?: string,
     * }  $alert
     */
    protected function caseAlertMail(array $alert): MailMessage
    {
        $case = $alert['case'];
        $case->loadMissing('department');

        $payload = [
            'case' => $case,
            'headline' => $alert['headline'],
            'intro' => $alert['intro'],
            'badge' => $alert['badge'] ?? $case->status->label(),
            'badgeColors' => $case->status->pillColors(),
            'placeLine' => $this->casePlaceLine($case),
            'metaLine' => $this->caseMetaLine($case),
            'actionUrl' => route('cases.show', $case),
            'settingsUrl' => route('profile.show'),
            'officeName' => (string) config('cases.office_name'),
        ];

        return (new MailMessage)
            ->subject($alert['subject'])
            ->view('emails.case-alert', $payload)
            ->text('emails.case-alert-text', $payload);
    }

    private function casePlaceLine(CaseModel $case): string
    {
        $place = str((string) $case->complainant_address)
            ->before(',')
            ->before("\n")
            ->trim();

        if ($place->isNotEmpty() && $place->length() <= 48) {
            return $case->complainant_name.' · '.$place;
        }

        $department = $case->department?->name;

        return $department !== null
            ? $case->complainant_name.' · '.$department
            : $case->complainant_name;
    }

    private function caseMetaLine(CaseModel $case): string
    {
        $days = $case->daysOpen();

        return sprintf(
            '%s · logged %s · %d day%s open',
            $case->case_number,
            $case->created_at->timezone(config('app.timezone'))->format('d M Y'),
            $days,
            $days === 1 ? '' : 's',
        );
    }

    /**
     * "CASE-2026-0008" → "#0008", matching the alert mock.
     */
    protected function caseShortNumber(CaseModel $case): string
    {
        if (preg_match('/(\d+)$/', $case->case_number, $matches) === 1) {
            return '#'.$matches[1];
        }

        return $case->case_number;
    }
}
