<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Http\Requests\StoreCaseRequest;
use App\Http\Requests\UpdateCaseRequest;
use App\Models\CaseModel;
use App\Services\AttachmentStorageService;
use App\Services\CaseRegisterQuery;
use App\Services\CaseWorkflowService;
use App\Services\DashboardStatsService;
use App\Services\DepartmentDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function __construct(
        private readonly CaseWorkflowService $workflow,
        private readonly CaseRegisterQuery $register,
        private readonly DepartmentDirectory $departments,
        private readonly AttachmentStorageService $attachments,
        private readonly DashboardStatsService $stats,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CaseModel::class);

        $filters = CaseRegisterQuery::filtersFrom($request);

        return view('cases.index', [
            'cases' => $this->register->paginate($filters),
            'filters' => $filters,
            'departments' => $this->departments->availableTo($request->user()),
            'statuses' => CaseStatus::options(),
            'priorities' => CasePriority::options(),
            'heading' => $request->boolean('high_only') ? 'High Priority Cases' : 'All Cases',
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CaseModel::class);

        return view('cases.create', [
            'departments' => $this->departments->availableTo($request->user()),
            'priorities' => CasePriority::cases(),
        ]);
    }

    public function store(StoreCaseRequest $request): RedirectResponse
    {
        $user = $request->user();

        $case = $this->workflow->createCase([
            ...$request->safe()->except('attachments'),
            'priority' => CasePriority::from($request->string('priority')->toString()),
        ], $user);

        if ($request->hasFile('attachments')) {
            $this->attachments->storeMany($case, $user, $request->file('attachments'));
        }

        $this->stats->forget($user);

        return redirect()
            ->route('cases.show', $case)
            ->with('status', sprintf(
                'Case logged and forwarded to the AC office. Reference number %s.',
                $case->case_number,
            ));
    }

    public function show(Request $request, CaseModel $case): View
    {
        $this->authorize('view', $case);

        // Every relation the Case Detail page touches, loaded up front — this
        // page reads from five related tables and is the worst N+1 offender.
        $case->load([
            'department',
            'creator',
            'assignee',
            'comments.user',
            'comments.department',
            'comments.forwardedToDepartment',
            'attachments.uploader',
            'activityLogs.user',
            'routingSteps.department',
        ]);

        $user = $request->user();

        return view('cases.show', [
            'case' => $case,
            'statuses' => CaseStatus::options(),
            'priorities' => CasePriority::cases(),
            'forwardTargets' => $this->departments->active()
                ->reject(fn ($department): bool => (int) $department->id === (int) $case->department_id)
                ->values(),
            'actingDepartments' => $this->actingDepartmentsFor($request, $case),
        ]);
    }

    public function update(UpdateCaseRequest $request, CaseModel $case): RedirectResponse
    {
        $user = $request->user();

        $this->workflow->changePriority(
            $case,
            CasePriority::from($request->string('priority')->toString()),
            $user,
        );

        $this->workflow->changeStatus(
            $case,
            CaseStatus::from($request->string('status')->toString()),
            $user,
        );

        $this->stats->forget($user);

        return back()->with('status', 'Case updated.');
    }

    /**
     * Departments the signed-in officer may speak for on this case. A user with
     * access to three departments picks one; a single-department user sees one.
     */
    private function actingDepartmentsFor(Request $request, CaseModel $case)
    {
        $user = $request->user();
        $involved = $case->involvedDepartmentIds();

        if ($user->isSuperAdmin()) {
            return $this->departments->active()
                ->filter(fn ($department): bool => in_array((int) $department->id, $involved, true))
                ->values();
        }

        return $user->departments
            ->filter(fn ($department): bool => in_array((int) $department->id, $involved, true))
            ->values();
    }
}
