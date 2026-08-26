<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Officers may correct their own name and phone. Email and designation
     * stay with the provisioning admin — even if this Fortify endpoint is hit
     * directly rather than through the profile form.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
        ])->validateWithBag('updateProfileInformation');

        $user->forceFill([
            'name' => $input['name'],
            'phone' => $input['phone'] ?? null,
        ])->save();
    }
}
