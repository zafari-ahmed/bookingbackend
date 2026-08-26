<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CasePriority;
use App\Enums\CaseSource;
use App\Enums\CaseStatus;
use App\Enums\RoutingAction;
use App\Models\CaseComment;
use App\Models\CaseModel;
use App\Models\CaseRouting;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single home for the assign → act → forward/return → complete workflow.
 *
 * Controllers translate HTTP into calls on this class and nothing more; the
 * routing history, status transitions and notification fan-out all live here so
 * that a queued job, a console command and a form submission behave identically.
 */
class CaseWorkflowService
{
    public function __construct(
        private readonly CaseNumberGenerator $caseNumbers,
        private readonly NotificationDispatchService $notifications,
    ) {}

    /**
     * Log a new complaint and open its routing history.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createCase(array $attributes, User $actor, CaseSource $source = CaseSource::Staff): CaseModel
    {
        $department = $this->resolveDepartment($attributes['department_id']);

        $case = DB::transaction(function () use ($attributes, $actor, $department, $source): CaseModel {
            $case = CaseModel::create([
                'case_number' => $this->caseNumbers->next(),
                'complainant_name' => $attributes['complainant_name'],
                'complainant_cnic' => $attributes['complainant_cnic'] ?? null,
                'complainant_phone' => $attributes['complainant_phone'] ?? null,
                'complainant_address' => $attributes['complainant_address'] ?? null,
                'issue_summary' => $attributes['issue_summary'],
                'department_id' => $department->getKey(),
                'created_by' => $actor->getKey(),
                'priority' => $attributes['priority'] ?? CasePriority::Normal,
                'status' => CaseStatus::Assigned,
                'source' => $source,
            ]);

            $this->recordRouting($case, $department, $actor, RoutingAction::Assigned, $attributes['notes'] ?? null);

            return $case;
        });

        $this->notifications->caseAssigned($case, $department, $actor);

        return $case->fresh() ?? $case;
    }

    /**
     * Hand a case to a department for the first time (or re-open an assignment).
     */
    public function assignToDepartment(
        CaseModel $case,
        Department $department,
        User $actor,
        ?User $assignee = null,
        ?string $notes = null,
    ): CaseModel {
        return $this->route($case, $department, $actor, RoutingAction::Assigned, CaseStatus::Assigned, $notes, $assignee);
    }

    /**
     * Pass a case onward to another department, keeping the previous
     * department's access to the file intact through the routing history.
     */
    public function forwardToDepartment(
        CaseModel $case,
        Department $department,
        User $actor,
        ?string $notes = null,
    ): CaseModel {
        if ((int) $case->department_id === (int) $department->getKey()) {
            throw new RuntimeException('The case already sits with '.$department->name.'.');
        }

        return $this->route($case, $department, $actor, RoutingAction::Forwarded, CaseStatus::Referred, $notes);
    }

    /**
     * Send a case back to the department that last held it — typically because
     * it was misrouted or needs more information.
     */
    public function returnToDepartment(
        CaseModel $case,
        Department $department,
        User $actor,
        ?string $notes = null,
    ): CaseModel {
        return $this->route($case, $department, $actor, RoutingAction::Returned, CaseStatus::InProgress, $notes);
    }

    /**
     * Mark the department's work on a case finished.
     */
    public function completeCase(CaseModel $case, User $actor, ?string $notes = null): CaseModel
    {
        $department = $this->resolveDepartment($case->department_id);

        DB::transaction(function () use ($case, $department, $actor, $notes): void {
            $this->recordRouting($case, $department, $actor, RoutingAction::Completed, $notes);

            $case->forceFill([
                'status' => CaseStatus::Resolved,
                'resolved_at' => Carbon::now(),
            ])->save();
        });

        $this->notifications->caseResolved($case, $actor);

        return $case->refresh();
    }

    public function changeStatus(CaseModel $case, CaseStatus $status, User $actor): CaseModel
    {
        if ($case->status === $status) {
            return $case;
        }

        $case->status = $status;
        $case->resolved_at = $status === CaseStatus::Resolved ? Carbon::now() : null;
        $case->closed_at = $status === CaseStatus::Closed ? Carbon::now() : null;
        $case->save();

        match ($status) {
            CaseStatus::Escalated => $this->notifications->caseEscalated($case, $actor),
            CaseStatus::Resolved => $this->notifications->caseResolved($case, $actor),
            default => null,
        };

        return $case->refresh();
    }

    public function changePriority(CaseModel $case, CasePriority $priority, User $actor): CaseModel
    {
        if ($case->priority === $priority) {
            return $case;
        }

        $case->priority = $priority;
        $case->save();

        return $case->refresh();
    }

    /**
     * Add a remark, optionally forwarding the case in the same action — this is
     * the "comment and forward" affordance on the Case Detail screen.
     */
    public function addComment(
        CaseModel $case,
        User $author,
        Department $actingAs,
        string $body,
        ?Department $forwardTo = null,
    ): CaseComment {
        $this->guardActingDepartment($case, $author, $actingAs);

        $comment = DB::transaction(function () use ($case, $author, $actingAs, $body, $forwardTo): CaseComment {
            $comment = CaseComment::create([
                'case_id' => $case->getKey(),
                'user_id' => $author->getKey(),
                'department_id' => $actingAs->getKey(),
                'comment' => $body,
                'forwarded_to_department_id' => $forwardTo?->getKey(),
            ]);

            // A case sitting in "assigned" starts moving the moment someone
            // records the first remark against it.
            if ($case->status === CaseStatus::Assigned) {
                $case->forceFill(['status' => CaseStatus::InProgress])->save();
            }

            return $comment;
        });

        if ($forwardTo !== null) {
            $this->forwardToDepartment($case, $forwardTo, $author, $body);
        }

        $this->notifications->caseCommented($comment, $author);

        return $comment;
    }

    private function route(
        CaseModel $case,
        Department $department,
        User $actor,
        RoutingAction $action,
        CaseStatus $status,
        ?string $notes,
        ?User $assignee = null,
    ): CaseModel {
        DB::transaction(function () use ($case, $department, $actor, $action, $status, $notes, $assignee): void {
            $this->recordRouting($case, $department, $actor, $action, $notes, $assignee);

            $case->forceFill([
                'department_id' => $department->getKey(),
                'assigned_to_user_id' => $assignee?->getKey(),
                'status' => $status,
                'resolved_at' => null,
                'closed_at' => null,
            ])->save();
        });

        $this->notifications->caseAssigned($case, $department, $actor);

        return $case->refresh();
    }

    private function recordRouting(
        CaseModel $case,
        Department $department,
        User $actor,
        RoutingAction $action,
        ?string $notes,
        ?User $assignee = null,
    ): CaseRouting {
        return CaseRouting::create([
            'case_id' => $case->getKey(),
            'department_id' => $department->getKey(),
            'assigned_by' => $actor->getKey(),
            'assigned_to_user_id' => $assignee?->getKey(),
            'action' => $action,
            'notes' => $notes,
        ]);
    }

    /**
     * A multi-department officer picks which department they are speaking for,
     * but only from departments that both they and the case belong to.
     */
    private function guardActingDepartment(CaseModel $case, User $author, Department $actingAs): void
    {
        if ($author->isSuperAdmin()) {
            return;
        }

        $permitted = array_intersect(
            $author->accessibleDepartmentIds(),
            $case->involvedDepartmentIds(),
        );

        if (! in_array((int) $actingAs->getKey(), $permitted, true)) {
            throw new RuntimeException(
                'You cannot record a remark as '.$actingAs->name.' on this case.'
            );
        }
    }

    /**
     * Accepts a string id as well, because form input arrives that way and the
     * caller should not have to remember to cast it.
     */
    private function resolveDepartment(Department|int|string $department): Department
    {
        return $department instanceof Department
            ? $department
            : Department::findOrFail((int) $department);
    }
}
