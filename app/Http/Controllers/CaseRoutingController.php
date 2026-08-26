<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoutingAction;
use App\Http\Requests\RouteCaseRequest;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use App\Services\CaseWorkflowService;
use App\Services\DashboardStatsService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CaseRoutingController extends Controller
{
    public function __construct(
        private readonly CaseWorkflowService $workflow,
        private readonly DashboardStatsService $stats,
    ) {}

    public function store(RouteCaseRequest $request, CaseModel $case): RedirectResponse
    {
        $actor = $request->user();
        $notes = $request->string('notes')->toString() ?: null;

        $department = $request->filled('department_id')
            ? Department::findOrFail($request->integer('department_id'))
            : null;

        $assignee = $request->filled('assigned_to_user_id')
            ? User::findOrFail($request->integer('assigned_to_user_id'))
            : null;

        try {
            $message = match ($request->action()) {
                RoutingAction::Assigned => $this->assign($case, $department, $actor, $assignee, $notes),
                RoutingAction::Forwarded => $this->forward($case, $department, $actor, $notes),
                RoutingAction::Returned => $this->returnCase($case, $department, $actor, $notes),
                RoutingAction::Completed => $this->complete($case, $actor, $notes),
            };
        } catch (RuntimeException $exception) {
            return back()->withErrors(['action' => $exception->getMessage()]);
        }

        $this->stats->forget($actor);

        return back()->with('status', $message);
    }

    private function assign(CaseModel $case, ?Department $department, User $actor, ?User $assignee, ?string $notes): string
    {
        $this->workflow->assignToDepartment($case, $this->required($department), $actor, $assignee, $notes);

        return 'Case assigned to '.$department->name.'.';
    }

    private function forward(CaseModel $case, ?Department $department, User $actor, ?string $notes): string
    {
        $this->workflow->forwardToDepartment($case, $this->required($department), $actor, $notes);

        return 'Case forwarded to '.$department->name.'.';
    }

    private function returnCase(CaseModel $case, ?Department $department, User $actor, ?string $notes): string
    {
        $this->workflow->returnToDepartment($case, $this->required($department), $actor, $notes);

        return 'Case returned to '.$department->name.'.';
    }

    private function complete(CaseModel $case, User $actor, ?string $notes): string
    {
        $this->workflow->completeCase($case, $actor, $notes);

        return 'Case marked resolved.';
    }

    private function required(?Department $department): Department
    {
        return $department ?? throw new RuntimeException('Select a department for this action.');
    }
}
