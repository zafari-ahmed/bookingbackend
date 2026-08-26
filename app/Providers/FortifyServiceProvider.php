<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registration is disabled in config/fortify.php, so `createUsersUsing`
        // is intentionally not wired — accounts come from the admin screens.
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        $this->registerViews();
        $this->registerAuthentication();
        $this->registerRateLimiters();

        Password::defaults(fn (): Password => Password::min(6)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());
            //->uncompromised());

        Event::listen(Login::class, function (Login $event): void {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
    }

    /**
     * Fortify is headless: it owns the authentication logic, the application
     * owns the markup. Every view below matches design-system.md.
     */
    private function registerViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
    }

    /**
     * A deactivated account must not be able to authenticate at all, so the
     * check happens during credential validation rather than afterwards.
     */
    private function registerAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', Str::lower($request->string('email')->toString()))->first();

            if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
                return null;
            }

            return $user->is_active ? $user : null;
        });
    }

    private function registerRateLimiters(): void
    {
        // Five attempts per minute per email + IP combination.
        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->session()->get('login.id')));
    }
}
