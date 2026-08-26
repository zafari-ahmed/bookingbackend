<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
    }

    public function test_an_active_officer_can_sign_in(): void
    {
        $user = $this->officer();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_sign_in_fails_with_the_wrong_password(): void
    {
        $user = $this->officer();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'not-the-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * A deactivated account is rejected at credential validation, so it never
     * reaches a session at all.
     */
    public function test_a_deactivated_officer_cannot_sign_in(): void
    {
        $user = $this->officer(['is_active' => false]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Five attempts per minute per email + IP, enforced by the `login` rate
     * limiter registered in FortifyServiceProvider.
     */
    public function test_login_is_throttled_after_five_attempts_a_minute(): void
    {
        $user = $this->officer();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();

        // The correct password does not get through either while locked out.
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }

    public function test_registration_routes_are_not_exposed(): void
    {
        $this->assertFalse(
            app('router')->has('register'),
            'Fortify registration must stay disabled — accounts are provisioned by administrators.',
        );

        $this->post('/register', [])->assertNotFound();
    }

    public function test_guests_are_redirected_to_the_login_screen(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    /**
     * Roles that can read CNIC data across departments are held at the
     * enrolment screen until they confirm an authenticator app.
     *
     * Which roles those are is an operator setting, so the test pins it rather
     * than inheriting whatever the current deployment has in .env.
     */
    public function test_privileged_roles_must_enrol_in_two_factor_before_reaching_the_register(): void
    {
        $this->requireTwoFactorFor(['department_admin']);

        $admin = $this->officer(['role' => 'department_admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('two-factor.setup'));

        $this->actingAs($this->withConfirmedTwoFactor($admin))
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_a_role_outside_the_requirement_is_not_forced_into_two_factor(): void
    {
        $this->requireTwoFactorFor(['department_admin']);

        $this->actingAs($this->officer())
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_clearing_the_setting_lets_every_role_straight_through(): void
    {
        $this->requireTwoFactorFor([]);

        $this->actingAs($this->officer(['role' => 'department_admin']))
            ->get(route('dashboard'))
            ->assertOk();
    }

    /**
     * @param  array<int, string>  $roles
     */
    private function requireTwoFactorFor(array $roles): void
    {
        config()->set('fortify.two_factor_required_roles', $roles);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function officer(array $attributes = []): User
    {
        return User::factory()
            ->inDepartments($this->department)
            ->create($attributes);
    }
}
