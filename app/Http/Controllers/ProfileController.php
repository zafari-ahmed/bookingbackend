<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->loadMissing('departments');

        return view('profile.show', [
            'user' => $user,
            'canChoosePrimary' => $user->hasMultipleDepartments(),
            'enabled' => $user->two_factor_secret !== null,
            'confirmed' => $user->hasConfirmedTwoFactor(),
            'qrCodeSvg' => $user->two_factor_secret !== null
                ? $user->twoFactorQrCodeSvg()
                : null,
            'recoveryCodes' => $user->two_factor_secret !== null && $user->hasConfirmedTwoFactor()
                ? $user->recoveryCodes()
                : [],
            'canDisableTwoFactor' => ! $user->requiresTwoFactor(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ])->save();

        if (array_key_exists('primary_department_id', $data)) {
            $user->makePrimary(Department::query()->findOrFail((int) $data['primary_department_id']));
        }

        return back()->with('status', 'Your profile has been updated.');
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('status', 'Your password has been updated.');
    }
}
