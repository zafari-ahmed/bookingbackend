<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityType;
use App\Models\CaseActivityLog;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $officer;

    private CaseModel $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->officer = User::factory()->inDepartments($this->department)->create();
        $this->case = CaseModel::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->officer->id,
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('activity.index'))->assertRedirect(route('login'));
    }

    public function test_an_officer_sees_only_their_own_activity_newest_first(): void
    {
        $other = User::factory()->inDepartments($this->department)->create();

        $older = $this->record($this->officer, 'Logged the water-supply complaint.', now()->subHours(5));
        $newer = $this->record($this->officer, 'Added a remark for the complainant.', now()->subHour());
        $this->record($other, 'Someone else forwarded this case.', now()->subMinutes(10));

        $response = $this->actingAs($this->officer)
            ->get(route('activity.index', ['period' => 'all']))
            ->assertOk()
            ->assertSee('Added a remark for the complainant.')
            ->assertSee('Logged the water-supply complaint.')
            ->assertDontSee('Someone else forwarded this case.')
            ->assertSee($this->case->case_number);

        $html = $response->getContent();
        $this->assertTrue(
            strpos($html, 'Added a remark for the complainant.') < strpos($html, 'Logged the water-supply complaint.'),
            'Newer activity must appear above older activity.',
        );
        $this->assertNotNull($older);
        $this->assertNotNull($newer);
    }

    public function test_the_recent_filter_hides_activity_older_than_three_days(): void
    {
        $this->record($this->officer, 'Fresh remark on the file.', now()->subDay());
        $this->record($this->officer, 'Old status change from last month.', now()->subDays(10));

        $this->actingAs($this->officer)
            ->get(route('activity.index'))
            ->assertOk()
            ->assertSee('Fresh remark on the file.')
            ->assertDontSee('Old status change from last month.');
    }

    public function test_last_week_includes_the_last_seven_days_only(): void
    {
        $this->record($this->officer, 'Remark from five days ago.', now()->subDays(5));
        $this->record($this->officer, 'Remark from three weeks ago.', now()->subDays(21));

        $this->actingAs($this->officer)
            ->get(route('activity.index', ['period' => 'week']))
            ->assertOk()
            ->assertSee('Remark from five days ago.')
            ->assertDontSee('Remark from three weeks ago.');
    }

    public function test_last_month_includes_the_last_thirty_days_only(): void
    {
        $this->record($this->officer, 'Remark from two weeks ago.', now()->subDays(14));
        $this->record($this->officer, 'Remark from last quarter.', now()->subDays(45));

        $this->actingAs($this->officer)
            ->get(route('activity.index', ['period' => 'month']))
            ->assertOk()
            ->assertSee('Remark from two weeks ago.')
            ->assertDontSee('Remark from last quarter.');
    }

    public function test_all_shows_activity_outside_the_shorter_windows(): void
    {
        $this->record($this->officer, 'Remark from last quarter.', now()->subDays(45));

        $this->actingAs($this->officer)
            ->get(route('activity.index', ['period' => 'all']))
            ->assertOk()
            ->assertSee('Remark from last quarter.');
    }

    public function test_the_activity_list_is_paginated(): void
    {
        foreach (range(1, 25) as $index) {
            $this->record($this->officer, "Remark number {$index}.", now()->subMinutes($index));
        }

        $response = $this->actingAs($this->officer)
            ->get(route('activity.index', ['period' => 'all']))
            ->assertOk();

        $logs = $response->viewData('logs');

        $this->assertSame((int) config('cases.per_page', 20), $logs->perPage());
        $this->assertCount($logs->perPage(), $logs->items());
        $this->assertTrue($logs->hasPages());
    }

    private function record(User $actor, string $description, mixed $when): CaseActivityLog
    {
        $log = CaseActivityLog::factory()->create([
            'case_id' => $this->case->id,
            'user_id' => $actor->id,
            'action_type' => ActivityType::CommentAdded,
            'description' => $description,
        ]);

        $log->forceFill(['created_at' => $when])->save();

        return $log->refresh();
    }
}
