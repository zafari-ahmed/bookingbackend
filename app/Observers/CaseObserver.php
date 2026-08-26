<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ActivityType;
use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Models\CaseActivityLog;
use App\Models\CaseModel;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;

/**
 * Writes the audit trail for every mutation of a case.
 *
 * Keeping this in an observer rather than in controllers means the log stays
 * complete no matter which code path touches the case — HTTP request, queued
 * job, console command or seeder.
 */
class CaseObserver
{
    public function created(CaseModel $case): void
    {
        $this->log($case, ActivityType::Created, sprintf(
            'Case logged for %s and routed to %s.',
            $case->complainant_name,
            $this->departmentName($case->department_id),
        ));
    }

    public function updated(CaseModel $case): void
    {
        if ($case->wasChanged('status')) {
            $this->logStatusChange($case);
        }

        if ($case->wasChanged('priority')) {
            $this->logPriorityChange($case);
        }

        if ($case->wasChanged('department_id')) {
            $this->logReassignment($case);
        }
    }

    private function logStatusChange(CaseModel $case): void
    {
        /** @var CaseStatus $old */
        $old = $this->enumFrom(CaseStatus::class, $case->getOriginal('status'));
        $new = $case->status;

        $type = match (true) {
            $new === CaseStatus::Escalated => ActivityType::Escalated,
            $new === CaseStatus::Resolved => ActivityType::Resolved,
            $new === CaseStatus::Closed => ActivityType::Closed,
            $old->isTerminal() && $new->isOpen() => ActivityType::Reopened,
            default => ActivityType::StatusChanged,
        };

        $this->log(
            $case,
            $type,
            sprintf('Status changed: %s → %s.', $old->label(), $new->label()),
            ['old_value' => $old->value, 'new_value' => $new->value],
        );
    }

    private function logPriorityChange(CaseModel $case): void
    {
        /** @var CasePriority $old */
        $old = $this->enumFrom(CasePriority::class, $case->getOriginal('priority'));
        $new = $case->priority;

        $this->log(
            $case,
            ActivityType::PriorityChanged,
            sprintf('Priority changed: %s → %s.', $old->label(), $new->label()),
            ['old_value' => $old->value, 'new_value' => $new->value],
        );
    }

    private function logReassignment(CaseModel $case): void
    {
        $oldId = (int) $case->getOriginal('department_id');
        $newId = (int) $case->department_id;

        $this->log(
            $case,
            ActivityType::Forwarded,
            sprintf(
                'Case moved from %s to %s.',
                $this->departmentName($oldId),
                $this->departmentName($newId),
            ),
            ['old_value' => $oldId, 'new_value' => $newId],
        );
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function log(CaseModel $case, ActivityType $type, string $description, ?array $meta = null): void
    {
        CaseActivityLog::create([
            'case_id' => $case->getKey(),
            'user_id' => Auth::id(),
            'action_type' => $type,
            'description' => $description,
            'meta' => $meta,
        ]);
    }

    /**
     * @template TEnum of \BackedEnum
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum
     */
    private function enumFrom(string $enum, mixed $value): mixed
    {
        return $value instanceof $enum ? $value : $enum::from((string) $value);
    }

    private function departmentName(?int $departmentId): string
    {
        if ($departmentId === null) {
            return 'an unassigned desk';
        }

        return Department::withTrashed()->find($departmentId)?->name ?? 'a removed department';
    }
}
