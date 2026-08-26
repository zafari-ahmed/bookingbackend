<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Services\CaseRegisterQuery;
use App\Services\DashboardStatsService;
use App\Services\DepartmentDirectory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CaseRegisterQuery $register,
        private readonly DashboardStatsService $stats,
        private readonly DepartmentDirectory $departments,
    ) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $filters = CaseRegisterQuery::filtersFrom($request);

        return view('dashboard.index', [
            'stats' => $this->stats->forUser($user),
            'cases' => $this->register->paginate($filters),
            'filters' => $filters,
            'departments' => $this->departments->availableTo($user),
            'statuses' => CaseStatus::options(),
            'priorities' => CasePriority::options(),
        ]);
    }
}
