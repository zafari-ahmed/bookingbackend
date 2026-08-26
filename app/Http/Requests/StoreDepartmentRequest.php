<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('departments', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'focal_person' => ['nullable', 'string', 'max:200'],
            'color_tag' => [
                'required',
                Rule::in(['protection', 'services', 'enforcement', 'welfare', 'civic']),
            ],
        ];
    }
}
