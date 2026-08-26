<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $actor = $this->user();

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')],
            'designation' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],

            // Only the AC office may mint another administrator; a department
            // admin can create ordinary officers and nothing above that.
            'role' => [
                'required',
                Rule::enum(UserRole::class),
                Rule::when(
                    ! $actor->isSuperAdmin(),
                    [Rule::in([UserRole::DepartmentUser->value])],
                ),
            ],

            'departments' => [
                Rule::requiredIf(fn (): bool => $this->input('role') !== UserRole::SuperAdmin->value),
                'nullable',
                'array',
                Rule::when(
                    $this->input('role') !== UserRole::SuperAdmin->value,
                    ['min:1'],
                ),
            ],
            'departments.*' => [
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
                Rule::when(
                    ! $actor->isSuperAdmin(),
                    [Rule::in($actor->accessibleDepartmentIds())],
                ),
            ],
            'primary_department_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Only the AC office may create administrator accounts.',
            'departments.*.in' => 'You may only grant access to departments you administer.',
        ];
    }
}
