<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Department $childProtection;

    private Department $revenue;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->childProtection = Department::factory()->create(['name' => 'Child Protection']);
        $this->revenue = Department::factory()->create(['name' => 'Revenue']);

        $this->officer = User::factory()->inDepartments($this->childProtection)->create([
            'name' => 'Sana Qureshi',
            'email' => 'sana.qureshi@sindh.gov.pk',
            'designation' => 'Clerk',
            'phone' => '0300-1111111',
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_the_profile_shows_email_and_designation_as_read_only(): void
    {
        $this->actingAs($this->officer)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('sana.qureshi@sindh.gov.pk')
            ->assertSee('Clerk')
            ->assertSee('Contact an administrator to change this address.')
            ->assertSee('Contact an administrator to update your designation.')
            ->assertDontSee('Primary department');
    }

    public function test_an_officer_with_one_department_does_not_see_the_primary_picker(): void
    {
        $html = $this->actingAs($this->officer)
            ->get(route('profile.show'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="primary_department_id"', $html);
    }

    public function test_an_officer_with_several_departments_can_change_their_primary(): void
    {
        $officer = User::factory()
            ->inDepartments([$this->childProtection, $this->revenue])
            ->create();

        $this->actingAs($officer)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Primary department')
            ->assertSee('Revenue');

        $this->actingAs($officer)
            ->patch(route('profile.update'), [
                'name' => $officer->name,
                'phone' => $officer->phone,
                'primary_department_id' => (string) $this->revenue->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame($this->revenue->id, $officer->refresh()->primaryDepartment()->id);
        $this->assertEqualsCanonicalizing(
            [$this->childProtection->id, $this->revenue->id],
            $officer->accessibleDepartmentIds(),
        );
    }

    public function test_an_officer_can_update_their_name_and_phone(): void
    {
        $this->actingAs($this->officer)
            ->patch(route('profile.update'), [
                'name' => 'Sana Qureshi Memon',
                'phone' => '0333-2222222',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->officer->refresh();

        $this->assertSame('Sana Qureshi Memon', $this->officer->name);
        $this->assertSame('0333-2222222', $this->officer->phone);
        $this->assertSame('sana.qureshi@sindh.gov.pk', $this->officer->email);
        $this->assertSame('Clerk', $this->officer->designation);
    }

    public function test_posting_email_or_designation_does_not_change_them(): void
    {
        $this->actingAs($this->officer)
            ->patch(route('profile.update'), [
                'name' => 'Sana Qureshi',
                'phone' => '0300-1111111',
                'email' => 'attacker@example.com',
                'designation' => 'Director',
            ])
            ->assertSessionHasNoErrors();

        $this->officer->refresh();

        $this->assertSame('sana.qureshi@sindh.gov.pk', $this->officer->email);
        $this->assertSame('Clerk', $this->officer->designation);
    }

    public function test_a_primary_department_outside_the_officers_grants_is_rejected(): void
    {
        $outsider = Department::factory()->create(['name' => 'Police']);

        $this->actingAs($this->officer)
            ->patch(route('profile.update'), [
                'name' => $this->officer->name,
                'phone' => $this->officer->phone,
                'primary_department_id' => (string) $outsider->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $this->childProtection->id,
            $this->officer->refresh()->primaryDepartment()->id,
            'A single-department officer has the primary field dropped rather than applied.',
        );
    }

    public function test_an_officer_can_change_their_password(): void
    {
        $this->actingAs($this->officer)
            ->patch(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'Correct-Horse-9-Staple',
                'password_confirmation' => 'Correct-Horse-9-Staple',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertTrue(Hash::check('Correct-Horse-9-Staple', $this->officer->refresh()->password));
    }

    public function test_a_wrong_current_password_is_rejected(): void
    {
        $this->actingAs($this->officer)
            ->from(route('profile.show'))
            ->patch(route('profile.password'), [
                'current_password' => 'not-the-password',
                'password' => 'Correct-Horse-9-Staple',
                'password_confirmation' => 'Correct-Horse-9-Staple',
            ])
            ->assertSessionHasErrors('current_password')
            ->assertRedirect(route('profile.show'));

        $this->assertTrue(Hash::check('password', $this->officer->refresh()->password));
    }

    public function test_fortify_cannot_be_used_to_change_the_login_email(): void
    {
        $this->actingAs($this->officer)
            ->put(route('user-profile-information.update'), [
                'name' => 'Sana Qureshi',
                'email' => 'attacker@example.com',
            ]);

        $this->assertSame('sana.qureshi@sindh.gov.pk', $this->officer->refresh()->email);
    }
}
