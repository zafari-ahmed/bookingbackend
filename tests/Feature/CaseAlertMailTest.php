<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CaseStatus;
use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use App\Notifications\AttachmentAddedNotification;
use App\Notifications\CaseAssignedNotification;
use App\Notifications\CaseEscalatedNotification;
use App\Notifications\CaseResolvedNotification;
use App\Notifications\CommentAddedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseAlertMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_escalation_mail_matches_the_alert_card_layout(): void
    {
        $department = Department::factory()->create(['name' => 'Water & Sewerage Board']);
        $officer = User::factory()->inDepartments($department)->create();
        $case = CaseModel::factory()
            ->forDepartment($department)
            ->loggedBy($officer)
            ->status(CaseStatus::Escalated)
            ->create([
                'case_number' => 'CASE-2026-0008',
                'complainant_name' => 'Hameeda Khatoon',
                'complainant_address' => 'Kotri, District Jamshoro',
                'issue_summary' => 'Illegal water hydrant beside a residential block; domestic supply at nil pressure.',
                'created_at' => now()->subDays(16),
            ]);

        $html = (string) (new CaseEscalatedNotification($case))->toMail($officer)->render();

        $this->assertStringContainsString('Government of Sindh', $html);
        $this->assertStringContainsString('District Coordination — Case Management System', $html);
        $this->assertStringContainsString('Escalated', $html);
        $this->assertStringContainsString('Case #0008 needs your action', $html);
        $this->assertStringContainsString('Water &amp; Sewerage Board', $html);
        $this->assertStringContainsString('15-day resolution window', $html);
        $this->assertStringContainsString('Hameeda Khatoon · Kotri', $html);
        $this->assertStringContainsString('Illegal water hydrant beside a residential block', $html);
        $this->assertStringContainsString('CASE-2026-0008', $html);
        $this->assertStringContainsString('16 days open', $html);
        $this->assertStringContainsString('Open case in the portal', $html);
        $this->assertStringContainsString(route('cases.show', $case), $html);
        $this->assertStringContainsString('Automated alert · Office of the Assistant Commissioner', $html);
        $this->assertStringContainsString('Manage notification settings', $html);
        $this->assertStringContainsString(route('profile.show'), $html);
        $this->assertStringContainsString('#132A45', $html);
        $this->assertStringContainsString('#2F7D6B', $html);
        $this->assertStringContainsString('#FBE3E3', $html);
    }

    public function test_every_case_alert_renders_the_shared_card(): void
    {
        $department = Department::factory()->create(['name' => 'Health']);
        $officer = User::factory()->inDepartments($department)->create();
        $case = CaseModel::factory()
            ->forDepartment($department)
            ->loggedBy($officer)
            ->status(CaseStatus::Assigned)
            ->create();

        $comment = CaseComment::factory()
            ->forCase($case)
            ->by($officer, $department)
            ->create(['comment' => 'Site visit completed.']);

        $mails = [
            (new CaseAssignedNotification($case, $department))->toMail($officer),
            (new CommentAddedNotification($comment->load(['case', 'user', 'department'])))->toMail($officer),
            (new AttachmentAddedNotification($case, ['complaint.jpg'], $officer->name))->toMail($officer),
            (new CaseResolvedNotification($case))->toMail($officer),
        ];

        foreach ($mails as $mail) {
            $html = (string) $mail->render();

            $this->assertStringContainsString('Government of Sindh', $html);
            $this->assertStringContainsString('Open case in the portal', $html);
            $this->assertStringContainsString($case->case_number, $html);
        }
    }
}
