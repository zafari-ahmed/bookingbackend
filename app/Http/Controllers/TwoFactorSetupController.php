<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landing page for roles that are required to enrol in two-factor
 * authentication before they can reach the case register.
 *
 * The enable/confirm/disable actions themselves are Fortify's own routes; this
 * only renders the screen that drives them.
 */
class TwoFactorSetupController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('auth.two-factor-setup', [
            'enabled' => $user->two_factor_secret !== null,
            'confirmed' => $user->hasConfirmedTwoFactor(),
            'qrCodeSvg' => $user->two_factor_secret !== null
                ? $user->twoFactorQrCodeSvg()
                : null,
            'recoveryCodes' => $user->two_factor_secret !== null && $user->hasConfirmedTwoFactor()
                ? $user->recoveryCodes()
                : [],
        ]);
    }
}
