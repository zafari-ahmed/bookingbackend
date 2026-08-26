<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CaseAttachment;
use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Writes case attachments to the private disk (storage/app/private), which sits
 * outside the web root. Files are only ever reachable through the authenticated,
 * policy-checked download route.
 */
class AttachmentStorageService
{
    public const DISK = 'private';

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, CaseAttachment>
     */
    public function storeMany(CaseModel $case, User $uploader, array $files, ?CaseComment $comment = null): array
    {
        return array_map(
            fn (UploadedFile $file): CaseAttachment => $this->store($case, $uploader, $file, $comment),
            array_values(array_filter($files)),
        );
    }

    public function store(CaseModel $case, User $uploader, UploadedFile $file, ?CaseComment $comment = null): CaseAttachment
    {
        // The stored name is randomised so a predictable path cannot be guessed
        // even if the private disk were ever mis-served. The disk is already
        // rooted at case-files, so the case number is the only prefix needed.
        $path = $file->storeAs(
            $case->case_number,
            Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
            self::DISK,
        );

        return CaseAttachment::create([
            'case_id' => $case->getKey(),
            'comment_id' => $comment?->getKey(),
            'uploaded_by' => $uploader->getKey(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}
