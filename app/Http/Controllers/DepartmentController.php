<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CaseStatus;
use App\Http\Requests\StoreDepartmentRequest;
use App\Models\CaseModel;
use App\Models\Department;
use App\Models\Scopes\DepartmentScope;
use App\Services\DepartmentDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentDirectory $directory) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Department::class);

        $user = $request->user();

        $departments = Department::query()
            ->withCount('users')
            ->when(
                ! $user->isSuperAdmin(),
                fn ($query) => $query->whereIn('id', $user->accessibleDepartmentIds())
            )
            ->orderBy('name')
            ->get();

        return view('departments.index', [
            'departments' => $departments,
            'openCaseCounts' => $this->openCaseCounts($departments->modelKeys()),
            'selected' => null,
        ]);
    }

    public function show(Request $request, Department $department): View
    {
        $this->authorize('view', $department);

        $user = $request->user();

        $departments = Department::query()
            ->withCount('users')
            ->when(
                ! $user->isSuperAdmin(),
                fn ($query) => $query->whereIn('id', $user->accessibleDepartmentIds())
            )
            ->orderBy('name')
            ->get();

        // A user assigned to several departments shows up in each of their
        // lists, with every assignment tagged and one marked primary.
        $department->load(['users' => fn ($query) => $query->with('departments')->orderBy('name')]);

        return view('departments.index', [
            'departments' => $departments,
            'openCaseCounts' => $this->openCaseCounts($departments->modelKeys()),
            'selected' => $department,
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        $this->directory->flush();

        return redirect()
            ->route('departments.show', $department)
            ->with('status', $department->name.' added to the department register.');
    }

    /**
     * Open case volume per department in one grouped query.
     *
     * @param  array<int, int>  $departmentIds
     * @return Collection<int, int>
     */
    private function openCaseCounts(array $departmentIds)
    {
        return CaseModel::query()
            // Department admins see the roll-up for departments they administer,
            // which is wider than the case-level scope allows.
            ->withoutGlobalScope(DepartmentScope::class)
            ->select('department_id', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('department_id', $departmentIds)
            ->whereIn('status', array_column(CaseStatus::openStatuses(), 'value'))
            ->groupBy('department_id')
            ->pluck('aggregate', 'department_id');
    }
}
