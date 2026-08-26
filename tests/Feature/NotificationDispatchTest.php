<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AttachmentAddedNotification;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseEscalatedNotification;
use App\Notifications\CaseResolvedNotification;
use App\Notifications\CommentAddedNotification;
use App\Services\CaseWorkflowService;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationDispatchTest extends TestCase
{
    use RefreshDatabase;

    private CaseWorkflowService $workflow;

    private Department $health;

    private Department $police;

    private User $healthOfficer;

    private User $healthColleague;

    private User $healthAdmin;

    private User $policeOfficer;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(CaseWorkflowService::class);
        $this->health = Department::factory()->create(['name' => 'Health']);
        $this->police = Department::factory()->create(['name' => 'Police']);
        $this->healthOfficer = User::factory()->inDepartments($this->health)->create();
        $this->healthColleague = User::factory()->inDepartments($this->health)->create();
        $this->healthAdmin = User::factory()->departmentAdmin()->inDepartments($this->health)->create();
        $this->policeOfficer = User::factory()->inDepartments($this->police)->create();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_assigning_a_case_notifies_the_receiving_department(): void
    {
        Notification::fake();

        $case = $this->case();

        Notification::assertSentTo(
            $this->healthColleague,
            CaseAssignedNotification::class,
            fn (CaseAssignedNotification $notification, array $channels): bool => $notification->case->is($case)
                && $channels === ['mail', 'database'],
        );
        Notification::assertSentTo($this->healthAdmin, CaseAssignedNotification::class);
        Notification::assertSentTo($this->superAdmin, CaseAssignedNotification::class);
        Notification::assertNotSentTo($this->policeOfficer, CaseAssignedNotification::class);
    }

    /**
     * Whoever triggered the event does not need telling about their own action.
     */
    public function test_the_acting_officer_is_not_notified_of_their_own_action(): void
    {
        Notification::fake();

        $this->case();

        Notification::assertNotSentTo($this->healthOfficer, CaseAssignedNotification::class);
    }

    public function test_the_database_channel_is_written_in_the_same_request(): void
    {
        $case = CaseModel::factory()->create(['department_id' => $this->health->id]);

        $notification = new CaseAssignedNotification($case, $this->health);

        $this->assertSame(
            ['mail', 'database'],
            $notification->via($this->healthColleague),
            'The bell reads the database channel; it must not depend on a queue worker.',
        );
    }

    public function test_forwarding_notifies_the_department_receiving_the_case(): void
    {
        $case = $this->case();

        Notification::fake();

        $this->workflow->forwardToDepartment($case, $this->police, $this->healthOfficer);

        Notification::assertSentTo($this->policeOfficer, CaseAssignedNotification::class);
        Notification::assertSentTo($this->superAdmin, CaseAssignedNotification::class);
    }

    public function test_a_remark_notifies_every_department_involved_in_the_case(): void
    {
        $case = $this->case();
        $this->workflow->forwardToDepartment($case, $this->police, $this->healthOfficer);

        Notification::fake();

        $this->workflow->addComment($case, $this->policeOfficer, $this->police, 'Statements recorded.');

        Notification::assertSentTo($this->healthOfficer, CommentAddedNotification::class);
        Notification::assertSentTo($this->healthColleague, CommentAddedNotification::class);
        Notification::assertSentTo($this->healthAdmin, CommentAddedNotification::class);
        Notification::assertSentTo($this->superAdmin, CommentAddedNotification::class);
        Notification::assertNotSentTo($this->policeOfficer, CommentAddedNotification::class);
    }

    public function test_uploading_a_file_notifies_the_holding_department_and_the_ac_office(): void
    {
        Storage::fake('private');

        $case = $this->case();

        Notification::fake();

        $this->actingAs($this->healthOfficer)
            ->post(route('cases.attachments.store', $case), [
                'attachments' => [UploadedFile::fake()->create('complaint.jpg', 80, 'image/jpeg')],
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($this->healthColleague, AttachmentAddedNotification::class);
        Notification::assertSentTo($this->healthAdmin, AttachmentAddedNotification::class);
        Notification::assertSentTo($this->superAdmin, AttachmentAddedNotification::class);
        Notification::assertNotSentTo($this->healthOfficer, AttachmentAddedNotification::class);
        Notification::assertNotSentTo($this->policeOfficer, AttachmentAddedNotification::class);
    }

    /**
     * An escalation is exactly what the AC office exists to catch, so super
     * admins hear about it alongside the holding department.
     */
    public function test_escalating_a_case_also_notifies_the_ac_office(): void
    {
        $case = $this->case();

        Notification::fake();

        $this->workflow->changeStatus($case, CaseStatus::Escalated, $this->healthOfficer);

        Notification::assertSentTo($this->superAdmin, CaseEscalatedNotification::class);
        Notification::assertSentTo($this->healthColleague, CaseEscalatedNotification::class);
        Notification::assertSentTo($this->healthAdmin, CaseEscalatedNotification::class);
    }

    public function test_resolving_a_case_notifies_the_involved_departments(): void
    {
        $case = $this->case();

        Notification::fake();

        $this->workflow->completeCase($case, $this->healthOfficer, 'Supply restored.');

        Notification::assertSentTo($this->healthColleague, CaseResolvedNotification::class);
        Notification::assertSentTo($this->superAdmin, CaseResolvedNotification::class);
    }

    public function test_deactivated_officers_stop_receiving_notifications(): void
    {
        $this->healthColleague->update(['is_active' => false]);

        Notification::fake();

        $this->case();

        Notification::assertNotSentTo($this->healthColleague, CaseAssignedNotification::class);
    }

    public function test_a_database_notification_lands_on_the_notifications_page(): void
    {
        $case = $this->case();

        $this->healthColleague->refresh();
        $this->assertSame(1, $this->healthColleague->unreadNotifications()->count());

        $notification = $this->healthColleague->notifications()->sole();
        $this->assertSame('assignments', $notification->data['group']);

        $this->actingAs($this->healthColleague)
            ->get(route('notifications.index', ['tab' => 'assignments']))
            ->assertOk()
            ->assertSee($case->case_number);
    }

    /**
     * Opening a notification marks it read and lands on the case it concerns.
     */
    public function test_opening_a_notification_marks_it_read_and_redirects_to_the_case(): void
    {
        $case = $this->case();
        $notification = $this->healthColleague->notifications()->sole();

        $this->actingAs($this->healthColleague)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('cases.show', $case));

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_one_officer_cannot_open_another_officers_notification(): void
    {
        $this->case();
        $notification = $this->healthColleague->notifications()->sole();

        $this->actingAs($this->policeOfficer)
            ->get(route('notifications.open', $notification))
            ->assertForbidden();
    }

    /**
     * The bell count is cached per user so a COUNT query does not fire on every
     * page load; dispatching must invalidate it.
     */
    public function test_the_unread_bell_count_is_cached_and_invalidated_on_dispatch(): void
    {
        $notifications = app(NotificationDispatchService::class);

        $this->assertSame(0, $notifications->unreadCountFor($this->healthColleague));

        $this->case();

        $this->assertSame(1, $notifications->unreadCountFor($this->healthColleague->refresh()));
    }

    private function case(): CaseModel
    {
        return $this->workflow->createCase([
            'complainant_name' => 'Abdul Rasheed Memon',
            'complainant_phone' => '0300-1234567',
            'issue_summary' => 'No medicine supply at the rural health centre for three weeks.',
            'department_id' => $this->health->id,
        ], $this->healthOfficer);
    }
}
