<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CaseModel;
use App\Models\Department;
use App\Models\Scopes\DepartmentScope;
use App\Models\User;
use App\Services\CaseWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The primary defence against cross-department data leakage.
 *
 * DepartmentScope is a global scope on CaseModel, so these assertions cover
 * every route and every relationship that reaches a case — not just the two
 * controllers exercised directly below.
 */
class DepartmentScopingTest extends TestCase
{
    use RefreshDatabase;

    private Department $childProtection;

    private Department $police;

    private User $childProtectionOfficer;

    private CaseModel $policeCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->childProtection = Department::factory()->create(['name' => 'Child Protection']);
        $this->police = Department::factory()->create(['name' => 'Police']);

        $this->childProtectionOfficer = User::factory()
            ->inDepartments($this->childProtection)
            ->create();

        $policeOfficer = User::factory()->inDepartments($this->police)->create();

        $this->policeCase = CaseModel::factory()->create([
            'department_id' => $this->police->id,
            'created_by' => $policeOfficer->id,
            'complainant_name' => 'Confidential Complainant',
        ]);
    }

    public function test_a_department_user_cannot_see_another_departments_case_in_the_register(): void
    {
        $this->actingAs($this->childProtectionOfficer)
            ->get(route('cases.index'))
            ->assertOk()
            ->assertDontSee($this->policeCase->case_number)
            ->assertDontSee('Confidential Complainant');
    }

    public function test_a_department_user_cannot_see_another_departments_case_on_the_dashboard(): void
    {
        $this->actingAs($this->childProtectionOfficer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($this->policeCase->case_number);
    }

    public function test_a_department_user_cannot_open_another_departments_case_detail_page(): void
    {
        $this->actingAs($this->childProtectionOfficer)
            ->get(route('cases.show', $this->policeCase))
            ->assertNotFound();
    }

    /**
     * Guessing a case number in the global search must not leak it either.
     */
    public function test_searching_for_another_departments_case_number_returns_nothing(): void
    {
        // The term itself is echoed back into the search box, so the leak to
        // check for is the case record behind it.
        $this->actingAs($this->childProtectionOfficer)
            ->get(route('cases.index', ['q' => $this->policeCase->case_number]))
            ->assertOk()
            ->assertDontSee('Confidential Complainant')
            ->assertDontSee(route('cases.show', $this->policeCase))
            ->assertSee('No cases match the current filters');
    }

    public function test_write_routes_on_another_departments_case_are_blocked(): void
    {
        $this->actingAs($this->childProtectionOfficer)
            ->patch(route('cases.update', $this->policeCase), [
                'status' => 'resolved',
                'priority' => 'urgent',
            ])
            ->assertNotFound();

        $this->actingAs($this->childProtectionOfficer)
            ->post(route('cases.comments.store', $this->policeCase), [
                'comment' => 'Trying to comment on a case I should not see.',
                'department_id' => $this->childProtection->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('case_comments', 0);
    }

    /**
     * The scope must bite at the query builder, not only in the controllers —
     * this is what protects console commands and future API routes too.
     */
    public function test_the_global_scope_filters_eloquent_queries_directly(): void
    {
        $this->actingAs($this->childProtectionOfficer);

        $this->assertSame(0, CaseModel::query()->count());
        $this->assertNull(CaseModel::find($this->policeCase->id));

        $this->assertSame(
            1,
            CaseModel::query()->withoutGlobalScope(DepartmentScope::class)->count(),
        );
    }

    public function test_the_super_admin_sees_every_department(): void
    {
        $superAdmin = $this->withConfirmedTwoFactor(
            User::factory()->superAdmin()->create()
        );

        $this->actingAs($superAdmin)
            ->get(route('cases.show', $this->policeCase))
            ->assertOk()
            ->assertSee($this->policeCase->case_number);
    }

    public function test_a_department_admin_sees_only_their_own_departments_cases(): void
    {
        $admin = User::factory()
            ->departmentAdmin()
            ->inDepartments($this->childProtection)
            ->create();

        $this->actingAs($admin)
            ->get(route('cases.index'))
            ->assertOk()
            ->assertDontSee($this->policeCase->case_number)
            ->assertDontSee('Confidential Complainant');

        $this->actingAs($admin)
            ->get(route('cases.show', $this->policeCase))
            ->assertNotFound();
    }

    /**
     * A multi-department officer sees the union of their departments — this is
     * what the department_user pivot exists to express.
     */
    public function test_a_multi_department_officer_sees_cases_from_every_department_they_belong_to(): void
    {
        $officer = User::factory()
            ->inDepartments([$this->childProtection, $this->police])
            ->create();

        $this->actingAs($officer)
            ->get(route('cases.show', $this->policeCase))
            ->assertOk();
    }

    /**
     * Once a case has passed through a department, that department keeps read
     * access via the routing history even after it moves on.
     */
    public function test_a_department_keeps_access_to_cases_routed_through_it(): void
    {
        app(CaseWorkflowService::class)->forwardToDepartment(
            $this->policeCase,
            $this->childProtection,
            User::factory()->superAdmin()->create(),
        );

        $this->actingAs($this->childProtectionOfficer)
            ->get(route('cases.show', $this->policeCase))
            ->assertOk();
    }
}
