<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');
        $actor = $this->user();

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignoreModel($target),
            ],
            'designation' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],

            // Optional: an admin resetting a password for an officer who has
            // lost access, rather than a required field on every save.
            'password' => ['nullable', 'confirmed', Password::defaults()],

            // Deactivation has its own policy check — a department admin may not
            // lock themselves out or touch the AC office. Dropping the field
            // rather than failing keeps the rest of the form saveable.
            'is_active' => [
                Rule::excludeIf(fn (): bool => $actor->cannot('toggleActive', $target)),
                'required', 'boolean',
            ],

            // Role changes are a privilege-escalation surface, so the field is
            // simply dropped unless the actor is allowed to change it.
            'role' => [
                Rule::excludeIf(fn (): bool => $actor->cannot('changeRole', $target)),
                'required',
                Rule::enum(UserRole::class),
            ],

            // Super admins see every department without a pivot grant. Ordinary
            // officers still need at least one, or they would disappear from
            // the register the moment their role was saved.
            'departments' => [
                Rule::requiredIf(fn (): bool => $this->input('role', $target->role->value) !== UserRole::SuperAdmin->value),
                'nullable',
                'array',
                Rule::when(
                    $this->input('role', $target->role->value) !== UserRole::SuperAdmin->value,
                    ['min:1'],
                ),
            ],
            'departments.*' => [
                'integer',
                Rule::exists('departments', 'id'),
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
            'departments.required' => 'Select at least one department for this officer.',
            'departments.*.in' => 'You may only grant access to departments you administer.',
        ];
    }
}
