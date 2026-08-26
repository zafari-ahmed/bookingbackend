<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\RoutingAction;
use App\Models\CaseActivityLog;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaseManagementTest extends TestCase
{
    use RefreshDatabase;

    private Department $health;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->health = Department::factory()->create(['name' => 'Health']);
        $this->officer = User::factory()->inDepartments($this->health)->create();
    }

    public function test_an_officer_can_log_a_new_case(): void
    {
        $this->actingAs($this->officer)
            ->post(route('cases.store'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $case = CaseModel::sole();

        $this->assertSame('Abdul Rasheed Memon', $case->complainant_name);
        $this->assertSame($this->health->id, $case->department_id);
        $this->assertSame($this->officer->id, $case->created_by);
        $this->assertSame(CasePriority::High, $case->priority);
        $this->assertSame(CaseStatus::Assigned, $case->status);
        $this->assertMatchesRegularExpression('/^CASE-\d{4}-\d{4}$/', $case->case_number);
    }

    /**
     * The observer writes the audit trail no matter which code path creates the
     * case, so it must be present straight after a plain form submission.
     */
    public function test_logging_a_case_opens_its_activity_log_and_routing_history(): void
    {
        $this->actingAs($this->officer)->post(route('cases.store'), $this->validPayload());

        $case = CaseModel::sole();

        $this->assertTrue(
            $case->activityLogs()->where('action_type', ActivityType::Created)->exists(),
            'A created-case activity log entry should be written by the observer.',
        );

        $this->assertDatabaseHas('case_routing', [
            'case_id' => $case->id,
            'department_id' => $this->health->id,
            'action' => RoutingAction::Assigned->value,
        ]);
    }

    public function test_case_creation_rejects_an_incomplete_submission(): void
    {
        $this->actingAs($this->officer)
            ->from(route('cases.create'))
            ->post(route('cases.store'), [
                'complainant_name' => '',
                'complainant_cnic' => '12345',
                'issue_summary' => 'Too short',
                'department_id' => $this->health->id,
                'priority' => 'catastrophic',
            ])
            ->assertRedirect(route('cases.create'))
            ->assertSessionHasErrors([
                'complainant_name',
                'complainant_cnic',
                'complainant_phone',
                'issue_summary',
                'priority',
            ]);

        $this->assertDatabaseCount('cases', 0);
    }

    /**
     * An officer may only file into a department they actually belong to,
     * otherwise the department register becomes a free-for-all.
     */
    public function test_an_officer_cannot_log_a_case_into_a_department_they_do_not_belong_to(): void
    {
        $police = Department::factory()->create(['name' => 'Police']);

        $this->actingAs($this->officer)
            ->post(route('cases.store'), [...$this->validPayload(), 'department_id' => (string) $police->id])
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseCount('cases', 0);
    }

    public function test_an_officer_can_update_status_and_priority_from_the_case_detail_screen(): void
    {
        $case = $this->case();

        $this->actingAs($this->officer)
            ->patch(route('cases.update', $case), [
                'status' => CaseStatus::InProgress->value,
                'priority' => CasePriority::Urgent->value,
            ])
            ->assertSessionHasNoErrors();

        $case->refresh();

        $this->assertSame(CaseStatus::InProgress, $case->status);
        $this->assertSame(CasePriority::Urgent, $case->priority);

        $this->assertTrue(
            $case->activityLogs()->where('action_type', ActivityType::StatusChanged)->exists(),
        );
        $this->assertTrue(
            $case->activityLogs()->where('action_type', ActivityType::PriorityChanged)->exists(),
        );
    }

    public function test_an_officer_can_add_a_remark_to_a_case(): void
    {
        $case = $this->case();

        $this->actingAs($this->officer)
            ->post(route('cases.comments.store', $case), [
                'comment' => 'Team visited the site and recorded statements.',
                'department_id' => (string) $this->health->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('case_comments', [
            'case_id' => $case->id,
            'user_id' => $this->officer->id,
            'department_id' => $this->health->id,
        ]);

        // The first remark moves an assigned case into progress.
        $this->assertSame(CaseStatus::InProgress, $case->refresh()->status);
    }

    public function test_attachments_are_stored_off_the_public_disk_and_served_through_a_guarded_route(): void
    {
        Storage::fake('private');

        $case = $this->case();

        $this->actingAs($this->officer)
            ->post(route('cases.attachments.store', $case), [
                'attachments' => [UploadedFile::fake()->create('complaint.jpg', 120, 'image/jpeg')],
            ])
            ->assertSessionHasNoErrors();

        $attachment = $case->attachments()->sole();

        Storage::disk('private')->assertExists($attachment->file_path);
        $this->assertStringNotContainsString('public', $attachment->file_path);

        $this->actingAs($this->officer)
            ->get(route('cases.attachments.download', [$case, $attachment]))
            ->assertOk();

        $view = $this->actingAs($this->officer)
            ->get(route('cases.attachments.view', [$case, $attachment]))
            ->assertOk();

        $this->assertStringStartsWith(
            'inline',
            (string) $view->headers->get('content-disposition'),
        );

        $this->assertTrue(
            CaseActivityLog::query()
                ->where('case_id', $case->id)
                ->where('user_id', $this->officer->id)
                ->where('action_type', ActivityType::AttachmentViewed)
                ->where('description', 'Viewed attachment "complaint.jpg".')
                ->exists(),
        );

        $this->actingAs($this->officer)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee(route('cases.attachments.view', [$case, $attachment]), false)
            ->assertSee('View complaint.jpg', false);

        // An officer outside the case's departments must not be able to pull
        // the file even with a valid attachment id.
        $outsider = User::factory()->inDepartments(Department::factory()->create())->create();

        $this->actingAs($outsider)
            ->get(route('cases.attachments.download', [$case, $attachment]))
            ->assertNotFound();

        $this->actingAs($outsider)
            ->get(route('cases.attachments.view', [$case, $attachment]))
            ->assertNotFound();

        $this->assertSame(
            1,
            CaseActivityLog::query()
                ->where('action_type', ActivityType::AttachmentViewed)
                ->count(),
            'A denied view must not leave an activity entry.',
        );
    }

    public function test_several_attachments_can_be_uploaded_together(): void
    {
        Storage::fake('private');

        $case = $this->case();

        $this->actingAs($this->officer)
            ->post(route('cases.attachments.store', $case), [
                'attachments' => [
                    UploadedFile::fake()->create('scan-front.jpg', 80, 'image/jpeg'),
                    UploadedFile::fake()->create('scan-back.jpg', 90, 'image/jpeg'),
                    UploadedFile::fake()->create('application.pdf', 200, 'application/pdf'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $case->attachments()->count());
        $this->assertEqualsCanonicalizing(
            ['scan-front.jpg', 'scan-back.jpg', 'application.pdf'],
            $case->attachments()->pluck('file_name')->all(),
        );
    }

    public function test_executables_are_rejected_by_the_upload_validator(): void
    {
        Storage::fake('private');

        $case = $this->case();

        $this->actingAs($this->officer)
            ->post(route('cases.attachments.store', $case), [
                'attachments' => [UploadedFile::fake()->create('payload.exe', 20, 'application/x-msdownload')],
            ])
            ->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, $case->attachments()->count());
    }

    public function test_the_case_register_is_searchable_by_case_number(): void
    {
        $case = $this->case();
        $other = $this->case(['complainant_name' => 'Someone Else']);

        $this->actingAs($this->officer)
            ->get(route('cases.index', ['q' => $case->case_number]))
            ->assertOk()
            ->assertSee($case->case_number)
            ->assertDontSee($other->case_number);
    }

    /**
     * Values are strings throughout, because that is how they arrive from an
     * HTML form — an int here would hide type bugs further down the stack.
     *
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'complainant_name' => 'Abdul Rasheed Memon',
            'complainant_cnic' => '41303-1234567-1',
            'complainant_phone' => '0300-1234567',
            'complainant_address' => 'Union Council 4, Latifabad, Hyderabad',
            'issue_summary' => 'The rural health centre has had no medicine supply for three weeks.',
            'department_id' => (string) $this->health->id,
            'priority' => CasePriority::High->value,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function case(array $attributes = []): CaseModel
    {
        return CaseModel::factory()->create([
            'department_id' => $this->health->id,
            'created_by' => $this->officer->id,
            'status' => CaseStatus::Assigned,
            'priority' => CasePriority::Normal,
            ...$attributes,
        ]);
    }
}
