<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\RoutingAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RouteCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('route', $this->route('case'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', new Enum(RoutingAction::class)],
            'department_id' => [
                Rule::requiredIf(fn (): bool => $this->input('action') !== RoutingAction::Completed->value),
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('is_active', true),
            ],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function action(): RoutingAction
    {
        return RoutingAction::from($this->string('action')->toString());
    }
}
