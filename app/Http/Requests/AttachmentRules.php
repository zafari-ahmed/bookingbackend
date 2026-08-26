<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rules\File;

/**
 * One definition of what the office is willing to accept as evidence, shared by
 * every form that takes an upload.
 *
 * The list is an allow-list of scanned-document and photograph types; anything
 * executable or archived is rejected before it reaches disk.
 */
final class AttachmentRules
{
    /**
     * @var array<int, string>
     */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /**
     * @var array<int, string>
     */
    public const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];

    public static function maxKilobytes(): int
    {
        return (int) config('cases.upload_max_kilobytes', 10240);
    }

    public static function forUploadedFile(): File
    {
        return File::types(self::ALLOWED_EXTENSIONS)
            ->max(self::maxKilobytes());
    }
}
