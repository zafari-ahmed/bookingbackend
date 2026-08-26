<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Every page in design-system.md section 6, rendered end to end against the
 * seeded dataset. Guards against Blade/component regressions that a unit test
 * of the services underneath would never catch.
 */
class SmokeRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seeded here rather than via $seed, which RefreshDatabase only honours
        // on the run's first migrate:fresh — these pages are checked against
        // the demo dataset itself.
        $this->seed();
    }

    public function test_login_screen_renders_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('District Coordination')
            ->assertSee('Sign In');
    }

    public function test_dashboard_renders_with_stat_cards_and_case_table(): void
    {
        $this->actingAs($this->departmentUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Cases')
            ->assertSee('Pending')
            ->assertSee('Resolved')
            ->assertSee('Overdue')
            ->assertSee('Recent Cases');
    }

    public function test_the_top_bar_clock_follows_the_application_timezone(): void
    {
        $this->travelTo(Carbon::parse('2026-08-26 12:00:00', 'UTC'));

        $this->actingAs($this->departmentUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('As of 26 Aug 2026, 5:00 PM', false)
            ->assertDontSee('As of 26 Aug 2026, 12:00 PM', false);
    }

    public function test_case_register_renders(): void
    {
        $this->actingAs($this->departmentUser())
            ->get(route('cases.index'))
            ->assertOk()
            ->assertSee('All Cases');
    }

    public function test_add_new_case_form_renders(): void
    {
        $this->actingAs($this->departmentUser())
            ->get(route('cases.create'))
            ->assertOk()
            ->assertSee('Complainant Details')
            ->assertSee('Assign to Department')
            ->assertSee('Drag files here')
            ->assertSee('several files')
            ->assertSee('Files cannot be deleted after they are uploaded.');
    }

    public function test_case_detail_renders_every_panel(): void
    {
        $user = $this->departmentUser();
        $case = CaseModel::query()
            ->whereIn('department_id', $user->accessibleDepartmentIds())
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee($case->case_number)
            ->assertSee($case->complainant_name)
            ->assertSee('Priority &amp; Status', false)
            ->assertSee('Attachments')
            ->assertSee('Files cannot be deleted after they are uploaded.')
            ->assertSee('Activity Log')
            ->assertSee('Remarks');
    }

    public function test_department_management_renders_for_super_admin(): void
    {
        $department = Department::query()->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee($department->name)
            ->assertSee('Open Cases')
            ->assertSee('Users');
    }

    public function test_add_user_page_renders_on_its_own_screen(): void
    {
        $department = Department::query()->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->get(route('users.create', ['department' => $department->id]))
            ->assertOk()
            ->assertSee('Officer Information')
            ->assertSee('Sign-in Password')
            ->assertSee('Access &amp; Rights', false)
            ->assertSee('Department Access')
            ->assertSee('Create user');
    }

    public function test_edit_user_page_renders_with_current_details(): void
    {
        $officer = $this->departmentUser();

        $this->actingAs($this->superAdmin())
            ->get(route('users.edit', $officer))
            ->assertOk()
            ->assertSee($officer->name)
            ->assertSee($officer->email)
            ->assertSee('Reset Password')
            ->assertSee('Account Status')
            ->assertSee('Primary Department')
            ->assertSee('Save changes');
    }

    public function test_profile_page_renders_self_service_fields(): void
    {
        $user = $this->departmentUser();

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSee('Personal details')
            ->assertSee('Contact an administrator to change this address.')
            ->assertSee('Change password')
            ->assertSee('Two-factor authentication');
    }

    public function test_activity_page_renders_period_filters(): void
    {
        $this->actingAs($this->departmentUser())
            ->get(route('activity.index'))
            ->assertOk()
            ->assertSee('Recent')
            ->assertSee('Last week')
            ->assertSee('Last month')
            ->assertSee('All');
    }

    public function test_notifications_page_renders_with_filter_tabs(): void
    {
        $user = User::query()
            ->where('role', 'department_user')
            ->whereHas('notifications')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Assignments')
            ->assertSee('Escalations');

        $this->actingAs($user)
            ->get(route('notifications.index', ['tab' => 'unread']))
            ->assertOk();
    }

    public function test_sidebar_navigation_is_present_on_every_inner_page(): void
    {
        $this->actingAs($this->departmentUser())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('All Cases')
            ->assertSee('New Case')
            ->assertSee('High Priority')
            ->assertSee('Notifications')
            ->assertSee('My Activity')
            ->assertSee('My Profile')
            ->assertSee('Log out')
            // Department and user directories are gated, so an ordinary officer
            // must not even be offered the nav items.
            ->assertDontSee('Departments')
            ->assertDontSee(route('users.index'), false);
    }

    public function test_the_user_directory_renders_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('All Users')
            ->assertSee('All Accounts')
            ->assertSee('Add User')
            ->assertSee('Users');
    }

    /**
     * Department users are exempt from the two-factor requirement, so they can
     * reach the pages directly without an enrolment redirect.
     */
    private function departmentUser(): User
    {
        return User::query()
            ->where('role', 'department_user')
            ->where('is_active', true)
            ->whereHas('departments')
            ->firstOrFail();
    }

    private function superAdmin(): User
    {
        return $this->withConfirmedTwoFactor(
            User::query()->where('role', 'super_admin')->firstOrFail()
        );
    }
}
