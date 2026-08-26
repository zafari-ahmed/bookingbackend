<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Provisioning and editing officer accounts. Both live on their own pages so
 * the department register stays a read-only list.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private Department $childProtection;

    private Department $revenue;

    private Department $police;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->childProtection = Department::factory()->create(['name' => 'Child Protection']);
        $this->revenue = Department::factory()->create(['name' => 'Revenue']);
        $this->police = Department::factory()->create(['name' => 'Police']);

        // The directory is cached, and these departments were made after boot.
        app(DepartmentDirectory::class)->flush();

        $this->admin = User::factory()
            ->departmentAdmin()
            ->inDepartments([$this->childProtection, $this->revenue])
            ->create();
    }

    public function test_the_department_register_links_out_to_the_add_user_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('departments.show', $this->childProtection))
            ->assertOk()
            ->assertSee(route('users.create', ['department' => $this->childProtection->id]), false)
            ->assertDontSee('name="password_confirmation"', false);
    }

    public function test_the_add_user_page_preselects_the_department_it_was_opened_from(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.create', ['department' => $this->revenue->id]))
            ->assertOk()
            ->assertSee('Officer Information')
            ->assertSee('Department Access')
            // Only the two departments this admin runs are on offer.
            ->assertSee('Revenue')
            ->assertDontSee('Police');
    }

    public function test_an_ordinary_officer_cannot_reach_the_add_user_page(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create();

        $this->actingAs($officer)
            ->get(route('users.create'))
            ->assertForbidden();
    }

    public function test_an_admin_provisions_an_officer_and_lands_back_on_the_department(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Farhan Ahmed Shaikh',
            'email' => 'farhan.shaikh@sindh.gov.pk',
            'designation' => 'Assistant Director',
            'phone' => '0300-1234567',
            'password' => 'Correct-Horse-9-Staple',
            'password_confirmation' => 'Correct-Horse-9-Staple',
            'role' => UserRole::DepartmentUser->value,
            'departments' => [(string) $this->revenue->id],
            'primary_department_id' => (string) $this->revenue->id,
        ]);

        $response->assertRedirect(route('departments.show', $this->revenue));

        $created = User::where('email', 'farhan.shaikh@sindh.gov.pk')->sole();

        $this->assertTrue($created->is_active);
        $this->assertNotNull($created->email_verified_at, 'The provisioning admin vouches for the address.');
        $this->assertSame([$this->revenue->id], $created->accessibleDepartmentIds());
        $this->assertSame($this->revenue->id, $created->primaryDepartment()->id);
    }

    public function test_a_department_admin_cannot_grant_access_to_a_department_they_do_not_run(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Outsider',
                'email' => 'outsider@sindh.gov.pk',
                'password' => 'Correct-Horse-9-Staple',
                'password_confirmation' => 'Correct-Horse-9-Staple',
                'role' => UserRole::DepartmentUser->value,
                'departments' => [(string) $this->police->id],
            ])
            ->assertSessionHasErrors('departments.0');

        $this->assertDatabaseMissing('users', ['email' => 'outsider@sindh.gov.pk']);
    }

    public function test_the_ac_office_directory_lists_every_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Shazia Junejo']);
        $policeOfficer = User::factory()->inDepartments($this->police)->create(['name' => 'Nasir Jokhio']);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('All Users')
            ->assertSee('Shazia Junejo')
            ->assertSee($this->admin->name)
            ->assertSee('Nasir Jokhio')
            ->assertSee('Revoke role')
            ->assertSee('Deactivate');
    }

    public function test_the_directory_can_be_filtered_by_role_and_department(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $policeOfficer = User::factory()->inDepartments($this->police)->create(['name' => 'Hidden Police Officer']);

        $this->actingAs($superAdmin)
            ->get(route('users.index', [
                'role' => UserRole::DepartmentAdmin->value,
                'department' => $this->childProtection->id,
            ]))
            ->assertOk()
            ->assertSee($this->admin->name)
            ->assertDontSee('Hidden Police Officer');
    }

    public function test_a_department_admin_cannot_open_the_global_user_directory(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_the_ac_office_can_reset_an_officers_password_from_edit(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $officer = User::factory()->inDepartments($this->childProtection)->create([
            'password' => Hash::make('Original-Password-9'),
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('users.update', $officer), [
                'name' => $officer->name,
                'email' => $officer->email,
                'is_active' => '1',
                'role' => $officer->role->value,
                'password' => 'Correct-Horse-9-Staple',
                'password_confirmation' => 'Correct-Horse-9-Staple',
                'departments' => [(string) $this->childProtection->id],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('Correct-Horse-9-Staple', $officer->refresh()->password));
    }

    public function test_the_ac_office_can_deactivate_and_reactivate_an_officer(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $officer = User::factory()->inDepartments($this->childProtection)->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.toggle-active', $officer))
            ->assertRedirect();

        $this->assertFalse($officer->refresh()->is_active);

        $this->actingAs($superAdmin)
            ->patch(route('users.toggle-active', $officer))
            ->assertRedirect();

        $this->assertTrue($officer->refresh()->is_active);
    }

    public function test_the_ac_office_cannot_deactivate_their_own_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.toggle-active', $superAdmin))
            ->assertForbidden();

        $this->assertTrue($superAdmin->refresh()->is_active);
    }

    public function test_the_ac_office_can_revoke_a_department_admin_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.revoke-role', $this->admin))
            ->assertRedirect();

        $this->assertSame(UserRole::DepartmentUser, $this->admin->refresh()->role);
        $this->assertNotEmpty($this->admin->accessibleDepartmentIds());
    }

    public function test_the_ac_office_cannot_revoke_its_own_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('users.revoke-role', $superAdmin))
            ->assertForbidden();

        $this->assertSame(UserRole::SuperAdmin, $superAdmin->refresh()->role);
    }

    public function test_a_department_admin_cannot_revoke_a_role(): void
    {
        $officer = User::factory()
            ->departmentAdmin()
            ->inDepartments($this->childProtection)
            ->create();

        $this->actingAs($this->admin)
            ->patch(route('users.revoke-role', $officer))
            ->assertForbidden();

        $this->assertSame(UserRole::DepartmentAdmin, $officer->refresh()->role);
    }

    public function test_the_edit_page_shows_the_officers_current_details_and_grants(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create([
            'name' => 'Sana Qureshi',
            'email' => 'sana.qureshi@sindh.gov.pk',
        ]);

        $this->actingAs($this->admin)
            ->get(route('users.edit', $officer))
            ->assertOk()
            ->assertSee('Sana Qureshi')
            ->assertSee('sana.qureshi@sindh.gov.pk')
            ->assertSee('Access &amp; Rights', false)
            // A department admin may not hand out roles.
            ->assertSee('Only the AC office may change a role.');
    }

    public function test_an_admin_edits_information_and_rights_in_one_save(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create([
            'name' => 'Sana Qureshi',
            'designation' => 'Clerk',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('users.update', $officer), [
                'name' => 'Sana Qureshi Memon',
                'email' => $officer->email,
                'designation' => 'Assistant Director',
                'phone' => '0333-7654321',
                'is_active' => '1',
                'departments' => [(string) $this->childProtection->id, (string) $this->revenue->id],
                'primary_department_id' => (string) $this->revenue->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $officer->refresh();

        $this->assertSame('Sana Qureshi Memon', $officer->name);
        $this->assertSame('Assistant Director', $officer->designation);
        $this->assertEqualsCanonicalizing(
            [$this->childProtection->id, $this->revenue->id],
            $officer->accessibleDepartmentIds(),
        );
        $this->assertSame($this->revenue->id, $officer->primaryDepartment()->id);
    }

    public function test_a_blank_password_on_edit_leaves_the_existing_one_alone(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create([
            'password' => Hash::make('Original-Password-9'),
        ]);

        $this->actingAs($this->admin)->patch(route('users.update', $officer), [
            'name' => $officer->name,
            'email' => $officer->email,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '1',
            'departments' => [(string) $this->childProtection->id],
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('Original-Password-9', $officer->refresh()->password));
    }

    public function test_a_department_admin_cannot_promote_an_officer(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create();

        $this->actingAs($this->admin)->patch(route('users.update', $officer), [
            'name' => $officer->name,
            'email' => $officer->email,
            'is_active' => '1',
            'role' => UserRole::SuperAdmin->value,
            'departments' => [(string) $this->childProtection->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            UserRole::DepartmentUser,
            $officer->refresh()->role,
            'The role field is dropped rather than applied when the actor may not set it.',
        );
    }

    public function test_the_ac_office_can_promote_an_officer(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $officer = User::factory()->inDepartments($this->childProtection)->create();

        $this->actingAs($superAdmin)->patch(route('users.update', $officer), [
            'name' => $officer->name,
            'email' => $officer->email,
            'is_active' => '1',
            'role' => UserRole::DepartmentAdmin->value,
            'departments' => [(string) $this->childProtection->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame(UserRole::DepartmentAdmin, $officer->refresh()->role);
    }

    /**
     * The edit form only offers the departments the actor runs, so a save must
     * not be read as an instruction to withdraw the ones it never showed.
     */
    public function test_saving_the_form_keeps_grants_the_acting_admin_cannot_see(): void
    {
        $officer = User::factory()
            ->inDepartments([$this->childProtection, $this->police])
            ->create();

        $this->actingAs($this->admin)->patch(route('users.update', $officer), [
            'name' => $officer->name,
            'email' => $officer->email,
            'is_active' => '1',
            'departments' => [(string) $this->childProtection->id],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$this->childProtection->id, $this->police->id],
            $officer->refresh()->accessibleDepartmentIds(),
        );
    }

    public function test_an_admin_cannot_edit_an_officer_from_another_department(): void
    {
        $outsider = User::factory()->inDepartments($this->police)->create();

        $this->actingAs($this->admin)
            ->get(route('users.edit', $outsider))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('users.update', $outsider), [
                'name' => 'Renamed',
                'email' => $outsider->email,
                'is_active' => '1',
                'departments' => [(string) $this->childProtection->id],
            ])
            ->assertForbidden();
    }

    /**
     * `update` and `toggleActive` are separate abilities — an admin may correct
     * their own contact details but must not be able to lock themselves out.
     */
    public function test_an_admin_cannot_deactivate_themselves_through_the_edit_form(): void
    {
        $this->actingAs($this->admin)->patch(route('users.update', $this->admin), [
            'name' => 'Still Me',
            'email' => $this->admin->email,
            'is_active' => '0',
            'departments' => [(string) $this->childProtection->id],
        ])->assertSessionHasNoErrors();

        $this->admin->refresh();

        $this->assertSame('Still Me', $this->admin->name);
        $this->assertTrue($this->admin->is_active);
    }

    public function test_an_empty_department_list_is_rejected_rather_than_stripping_all_access(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create();

        $this->actingAs($this->admin)
            ->patch(route('users.update', $officer), [
                'name' => $officer->name,
                'email' => $officer->email,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('departments');

        $this->assertSame([$this->childProtection->id], $officer->refresh()->accessibleDepartmentIds());
    }

    public function test_an_email_already_in_use_is_rejected(): void
    {
        $officer = User::factory()->inDepartments($this->childProtection)->create();

        $this->actingAs($this->admin)
            ->patch(route('users.update', $officer), [
                'name' => $officer->name,
                'email' => $this->admin->email,
                'is_active' => '1',
                'departments' => [(string) $this->childProtection->id],
            ])
            ->assertSessionHasErrors('email');
    }
}
