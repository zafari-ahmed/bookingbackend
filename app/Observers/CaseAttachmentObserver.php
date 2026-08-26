<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityType;
use App\Models\CaseActivityLog;
use App\Models\CaseAttachment;
use Illuminate\Support\Facades\Storage;

class CaseAttachmentObserver
{
    public function created(CaseAttachment $attachment): void
    {
        CaseActivityLog::create([
            'case_id' => $attachment->case_id,
            'user_id' => $attachment->uploaded_by,
            'action_type' => ActivityType::AttachmentAdded,
            'description' => sprintf('Attachment "%s" uploaded.', $attachment->file_name),
            'meta' => [
                'attachment_id' => $attachment->getKey(),
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
            ],
        ]);
    }

    /**
     * Attachments are the one case artefact that is hard-deleted, because the
     * blob must actually leave disk. The activity log entry survives.
     */
    public function deleted(CaseAttachment $attachment): void
    {
        Storage::disk('private')->delete($attachment->file_path);
    }
}
