<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadAttachment', $this->route('case'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attachments' => ['required', 'array', 'min:1', 'max:10'],
            'attachments.*' => AttachmentRules::forUploadedFile(),
        ];
    }
}
