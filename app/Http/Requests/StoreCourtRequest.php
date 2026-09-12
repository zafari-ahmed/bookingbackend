<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageFacilities() ?? false;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'exists:sports,id'],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'opening_time' => ['required', 'date_format:H:i'],
            'closing_time' => ['required', 'date_format:H:i', 'different:opening_time'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'closing_time.different' => 'Closing time must be different from opening time. For overnight courts use 17:00–03:00.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'opening_time' => substr((string) $this->input('opening_time'), 0, 5),
            'closing_time' => substr((string) $this->input('closing_time'), 0, 5),
        ]);
    }
}
