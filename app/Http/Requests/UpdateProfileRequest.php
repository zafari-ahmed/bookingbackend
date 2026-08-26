<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $departmentIds = $user->accessibleDepartmentIds();
        $canChoosePrimary = count($departmentIds) > 1;

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],

            // Email and designation stay with the provisioning admin; they are
            // not accepted here even if a crafted request sends them.
            'primary_department_id' => [
                Rule::excludeIf(! $canChoosePrimary),
                'required',
                'integer',
                Rule::in($departmentIds),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primary_department_id.in' => 'You can only set a department you already belong to as primary.',
        ];
    }
}
