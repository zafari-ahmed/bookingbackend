<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\CaseStatus;
use App\Enums\RoutingAction;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use App\Services\CaseWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The assign → act → forward → complete lifecycle that CaseWorkflowService owns.
 */
class CaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private CaseWorkflowService $workflow;

    private Department $health;

    private Department $police;

    private User $healthOfficer;

    private User $policeOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(CaseWorkflowService::class);
        $this->health = Department::factory()->create(['name' => 'Health']);
        $this->police = Department::factory()->create(['name' => 'Police']);
        $this->healthOfficer = User::factory()->inDepartments($this->health)->create();
        $this->policeOfficer = User::factory()->inDepartments($this->police)->create();
    }

    public function test_forwarding_moves_the_case_and_records_the_routing_step(): void
    {
        $case = $this->case();

        $this->workflow->forwardToDepartment($case, $this->police, $this->healthOfficer, 'Criminal element involved.');

        $case->refresh();

        $this->assertSame($this->police->id, $case->department_id);
        $this->assertSame(CaseStatus::Referred, $case->status);

        $this->assertDatabaseHas('case_routing', [
            'case_id' => $case->id,
            'department_id' => $this->police->id,
            'assigned_by' => $this->healthOfficer->id,
            'action' => RoutingAction::Forwarded->value,
            'notes' => 'Criminal element involved.',
        ]);
    }

    /**
     * The originating department keeps its view of the file after forwarding —
     * that is what makes the routing history, not just department_id, the basis
     * of the access rule.
     */
    public function test_the_originating_department_retains_access_after_forwarding(): void
    {
        $case = $this->case();

        $this->workflow->forwardToDepartment($case, $this->police, $this->healthOfficer);

        $this->actingAs($this->healthOfficer)
            ->get(route('cases.show', $case))
            ->assertOk();
    }

    public function test_forwarding_to_the_holding_department_is_rejected(): void
    {
        $case = $this->case();

        $this->expectException(RuntimeException::class);

        $this->workflow->forwardToDepartment($case, $this->health, $this->healthOfficer);
    }

    public function test_returning_a_case_sends_it_back_and_reopens_it(): void
    {
        $case = $this->case();

        $this->workflow->forwardToDepartment($case, $this->police, $this->healthOfficer);
        $this->workflow->returnToDepartment($case, $this->health, $this->policeOfficer, 'Not a police matter.');

        $case->refresh();

        $this->assertSame($this->health->id, $case->department_id);
        $this->assertSame(CaseStatus::InProgress, $case->status);
        $this->assertSame(3, $case->routingSteps()->count());
    }

    public function test_completing_a_case_resolves_it_and_stamps_the_resolution_time(): void
    {
        $case = $this->case();

        $this->workflow->completeCase($case, $this->healthOfficer, 'Medicine supply restored.');

        $case->refresh();

        $this->assertSame(CaseStatus::Resolved, $case->status);
        $this->assertNotNull($case->resolved_at);
        $this->assertDatabaseHas('case_routing', [
            'case_id' => $case->id,
            'action' => RoutingAction::Completed->value,
        ]);
    }

    public function test_the_full_lifecycle_is_visible_in_the_activity_timeline(): void
    {
        $case = $this->case();

        $this->workflow->addComment($case, $this->healthOfficer, $this->health, 'Site visit completed.');
        $this->workflow->forwardToDepartment($case, $this->police, $this->healthOfficer, 'Handing over.');
        $this->workflow->completeCase($case, $this->policeOfficer, 'FIR registered.');

        $recorded = $case->activityLogs()->pluck('action_type')->all();

        $this->assertContains(ActivityType::Created, $recorded);
        $this->assertContains(ActivityType::CommentAdded, $recorded);
        $this->assertContains(ActivityType::Forwarded, $recorded);
        $this->assertContains(ActivityType::Resolved, $recorded);
    }

    /**
     * A remark has to be filed under a department the author actually belongs
     * to, so a multi-department officer cannot speak for the wrong one.
     */
    public function test_an_officer_cannot_record_a_remark_as_a_department_they_do_not_belong_to(): void
    {
        $case = $this->case();

        $this->expectException(RuntimeException::class);

        $this->workflow->addComment($case, $this->healthOfficer, $this->police, 'Speaking out of turn.');
    }

    /**
     * Re-routing is a supervisory act, so it is a department admin — not an
     * ordinary officer — who drives it through the Case Detail screen.
     */
    public function test_routing_through_the_http_layer_uses_the_same_workflow(): void
    {
        $case = $this->case();

        $supervisor = $this->withConfirmedTwoFactor(
            User::factory()->departmentAdmin()->inDepartments($this->health)->create()
        );

        $this->actingAs($supervisor)
            ->post(route('cases.routing.store', $case), [
                'action' => RoutingAction::Forwarded->value,
                'department_id' => (string) $this->police->id,
                'notes' => 'Referred for investigation.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->police->id, $case->refresh()->department_id);
    }

    public function test_an_ordinary_officer_cannot_re_route_a_case(): void
    {
        $case = $this->case();

        $this->actingAs($this->healthOfficer)
            ->post(route('cases.routing.store', $case), [
                'action' => RoutingAction::Forwarded->value,
                'department_id' => $this->police->id,
            ])
            ->assertForbidden();

        $this->assertSame($this->health->id, $case->refresh()->department_id);
    }

    private function case(): CaseModel
    {
        return $this->workflow->createCase([
            'complainant_name' => 'Abdul Rasheed Memon',
            'complainant_phone' => '0300-1234567',
            'issue_summary' => 'No medicine supply at the rural health centre for three weeks.',
            // A string, as it arrives from the form.
            'department_id' => (string) $this->health->id,
        ], $this->healthOfficer);
    }
}
