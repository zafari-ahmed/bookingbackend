<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CasePriority;
use App\Models\CaseModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CaseModel::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'complainant_name' => ['required', 'string', 'max:150'],
            'complainant_cnic' => ['nullable', 'string', 'regex:/^\d{5}-\d{7}-\d$/'],
            'complainant_phone' => ['required', 'string', 'max:20'],
            'complainant_address' => ['nullable', 'string', 'max:1000'],
            'issue_summary' => ['required', 'string', 'min:20', 'max:5000'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),

                // A department user may only log a case into a department they
                // actually belong to; the AC office may log into any of them.
                Rule::when(
                    ! Auth::user()->isSuperAdmin(),
                    [Rule::in(Auth::user()->accessibleDepartmentIds())],
                ),
            ],
            'priority' => ['required', new Enum(CasePriority::class)],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => AttachmentRules::forUploadedFile(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'complainant_cnic.regex' => 'The CNIC must be in the format 00000-0000000-0.',
            'issue_summary.min' => 'Describe the complaint in at least 20 characters — this text appears in the AC office case register.',
            'department_id.in' => 'You may only log a case into a department you are assigned to.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'complainant_name' => 'complainant full name',
            'complainant_cnic' => 'CNIC number',
            'complainant_phone' => 'phone number',
            'issue_summary' => 'issue summary',
            'department_id' => 'department',
        ];
    }
}
