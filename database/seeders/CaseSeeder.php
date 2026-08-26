<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Enums\RoutingAction;
use App\Enums\UserRole;
use App\Models\CaseActivityLog;
use App\Models\CaseAttachment;
use App\Models\CaseModel;
use App\Models\CaseRouting;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CaseSeeder extends Seeder
{
    private const TOTAL_CASES = 28;

    /**
     * Roughly one in three seeded cases gets a multi-step routing history so the
     * Case Detail timeline and department-wise stats are populated on first run.
     */
    private const ROUTED_CASE_RATIO = 3;

    public function run(): void
    {
        /** @var Collection<int, Department> $departments */
        $departments = Department::all();

        /** @var User $acOfficer */
        $acOfficer = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $statusCycle = [
            CaseStatus::Pending,
            CaseStatus::Assigned,
            CaseStatus::InProgress,
            CaseStatus::InProgress,
            CaseStatus::Escalated,
            CaseStatus::Referred,
            CaseStatus::Resolved,
            CaseStatus::Closed,
        ];

        $priorityCycle = [
            CasePriority::Normal,
            CasePriority::High,
            CasePriority::Normal,
            CasePriority::Urgent,
            CasePriority::High,
            CasePriority::Normal,
        ];

        for ($index = 0; $index < self::TOTAL_CASES; $index++) {
            $department = $departments[$index % $departments->count()];
            $status = $statusCycle[$index % count($statusCycle)];
            $priority = $priorityCycle[$index % count($priorityCycle)];

            // Spread the register across the last two months, oldest first, so
            // the "overdue 15+ days" stat card has genuine content.
            $createdAt = Carbon::now()->subDays(58 - ($index * 2))->setTime(
                random_int(9, 16),
                random_int(0, 59),
            );

            $case = CaseModel::factory()
                ->forDepartment($department)
                ->loggedBy($acOfficer)
                ->create([
                    'case_number' => sprintf('CASE-%d-%04d', $createdAt->year, $index + 1),
                    'status' => $status,
                    'priority' => $priority,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                    'resolved_at' => $status === CaseStatus::Resolved
                        ? $createdAt->copy()->addDays(random_int(2, 9))
                        : null,
                    'closed_at' => $status === CaseStatus::Closed
                        ? $createdAt->copy()->addDays(random_int(6, 20))
                        : null,
                ]);

            $this->alignCreationLog($case, $acOfficer);
            $this->seedRouting($case, $department, $departments, $acOfficer, $index);

            if ($index % 4 === 0) {
                $this->seedAttachments($case, $acOfficer);
            }
        }
    }

    /**
     * The observer stamps the "created" entry with `now()` and a null actor
     * because seeding runs without an authenticated user. Backdate it so the
     * timeline reads correctly.
     */
    private function alignCreationLog(CaseModel $case, User $actor): void
    {
        CaseActivityLog::where('case_id', $case->getKey())
            ->where('action_type', ActivityType::Created)
            ->update([
                'user_id' => $actor->getKey(),
                'created_at' => $case->created_at,
            ]);
    }

    /**
     * @param  Collection<int, Department>  $departments
     */
    private function seedRouting(
        CaseModel $case,
        Department $owning,
        Collection $departments,
        User $acOfficer,
        int $index,
    ): void {
        $assignedAt = $case->created_at->copy()->addMinutes(random_int(20, 180));

        $this->recordRouting($case, $owning, $acOfficer, RoutingAction::Assigned, $assignedAt, sprintf(
            'Assigned to %s for departmental action.',
            $owning->name,
        ));

        $this->recordActivity($case, $acOfficer, ActivityType::Assigned, $assignedAt, sprintf(
            'Assigned to %s.',
            $owning->name,
        ));

        if ($index % self::ROUTED_CASE_RATIO !== 0) {
            return;
        }

        // A second department is pulled in — the case is forwarded, acted on and
        // (where the status allows) completed.
        $partner = $departments->where('id', '!=', $owning->getKey())
            ->random();

        $forwardedAt = $assignedAt->copy()->addDays(random_int(2, 6));

        $this->recordRouting($case, $partner, $acOfficer, RoutingAction::Forwarded, $forwardedAt, sprintf(
            'Forwarded to %s for assistance.',
            $partner->name,
        ));

        $this->recordActivity($case, $acOfficer, ActivityType::Forwarded, $forwardedAt, sprintf(
            'Forwarded to %s.',
            $partner->name,
        ));

        if (! $case->status->isTerminal()) {
            return;
        }

        $completedAt = $case->resolved_at ?? $case->closed_at ?? $forwardedAt->copy()->addDays(3);

        $this->recordRouting($case, $partner, $acOfficer, RoutingAction::Completed, $completedAt, sprintf(
            '%s reported completion of the required action.',
            $partner->name,
        ));

        $this->recordActivity(
            $case,
            $acOfficer,
            $case->status === CaseStatus::Resolved ? ActivityType::Resolved : ActivityType::Closed,
            $completedAt,
            sprintf('Status changed: In Progress → %s.', $case->status->label()),
        );
    }

    private function recordRouting(
        CaseModel $case,
        Department $department,
        User $actor,
        RoutingAction $action,
        Carbon $at,
        string $notes,
    ): void {
        CaseRouting::create([
            'case_id' => $case->getKey(),
            'department_id' => $department->getKey(),
            'assigned_by' => $actor->getKey(),
            'action' => $action,
            'notes' => $notes,
            'created_at' => $at,
        ]);
    }

    private function recordActivity(
        CaseModel $case,
        User $actor,
        ActivityType $type,
        Carbon $at,
        string $description,
    ): void {
        CaseActivityLog::create([
            'case_id' => $case->getKey(),
            'user_id' => $actor->getKey(),
            'action_type' => $type,
            'description' => $description,
            'created_at' => $at,
        ]);
    }

    /**
     * Attachment rows only — no blobs are written to the private disk, so the
     * download route correctly reports a missing file for seeded records.
     */
    private function seedAttachments(CaseModel $case, User $uploader): void
    {
        CaseAttachment::factory()
            ->count(random_int(1, 3))
            ->create([
                'case_id' => $case->getKey(),
                'uploaded_by' => $uploader->getKey(),
                'created_at' => $case->created_at->copy()->addHours(random_int(2, 40)),
            ]);
    }
}
