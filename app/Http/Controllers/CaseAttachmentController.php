<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Requests\UploadAttachmentRequest;
use App\Models\CaseActivityLog;
use App\Models\CaseAttachment;
use App\Models\CaseModel;
use App\Services\AttachmentStorageService;
use App\Services\NotificationDispatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseAttachmentController extends Controller
{
    public function __construct(
        private readonly AttachmentStorageService $attachments,
        private readonly NotificationDispatchService $notifications,
    ) {}

    public function store(UploadAttachmentRequest $request, CaseModel $case): RedirectResponse
    {
        $user = $request->user();
        $stored = $this->attachments->storeMany($case, $user, $request->file('attachments'));
        $this->notifications->attachmentsAdded($case, $user, $stored);

        return back()->with('status', 'Attachment uploaded to the case file.');
    }

    /**
     * Open the file in the browser (inline). Logged on the activity trail
     * because the case file is an official record — who looked at an exhibit
     * matters as much as who uploaded it.
     */
    public function view(Request $request, CaseModel $case, CaseAttachment $attachment): StreamedResponse
    {
        $disk = $this->readableDisk($case, $attachment);

        CaseActivityLog::create([
            'case_id' => $case->getKey(),
            'user_id' => $request->user()->getKey(),
            'action_type' => ActivityType::AttachmentViewed,
            'description' => sprintf('Viewed attachment "%s".', $attachment->file_name),
            'meta' => [
                'attachment_id' => $attachment->getKey(),
                'file_type' => $attachment->file_type,
            ],
        ]);

        return $disk->response($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->file_type,
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    /**
     * Attachments live on the private disk, outside the web root. This is the
     * only way to download one, and it re-checks the case policy every time.
     */
    public function download(CaseModel $case, CaseAttachment $attachment): StreamedResponse
    {
        $disk = $this->readableDisk($case, $attachment);

        return $disk->download($attachment->file_path, $attachment->file_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    private function readableDisk(CaseModel $case, CaseAttachment $attachment)
    {
        $this->authorize('view', $case);

        abort_unless((int) $attachment->case_id === (int) $case->getKey(), 404);

        $disk = Storage::disk(AttachmentStorageService::DISK);

        abort_unless($disk->exists($attachment->file_path), 404, 'The stored file is no longer available.');

        return $disk;
    }
}
