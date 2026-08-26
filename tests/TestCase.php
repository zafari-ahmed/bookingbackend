<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Privileged roles are held at the enrolment screen until they confirm an
     * authenticator app, so tests that act as one have to satisfy that first.
     */
    protected function withConfirmedTwoFactor(User $user): User
    {
        $user->forceFill([
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_recovery_codes' => encrypt(json_encode(['aaaaaaaaaa-bbbbbbbbbb'])),
            'two_factor_confirmed_at' => now(),
        ])->saveQuietly();

        return $user->refresh();
    }
}
