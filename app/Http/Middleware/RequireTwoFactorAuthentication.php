<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds privileged roles at the two-factor setup screen until they have
 * confirmed an authenticator app.
 *
 * `super_admin` and `department_admin` accounts can read CNIC and complainant
 * data across departments, so password-only access is not sufficient.
 */
class RequireTwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->requiresTwoFactor() || $user->hasConfirmedTwoFactor()) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.*', 'password.confirm', 'logout', 'user/*')) {
            return $next($request);
        }

        return redirect()->route('two-factor.setup')->with(
            'status',
            'Two-factor authentication is mandatory for your role. Set up an authenticator app to continue.'
        );
    }
}
