<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('case'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(CaseStatus::class)],
            'priority' => ['required', new Enum(CasePriority::class)],
        ];
    }
}
