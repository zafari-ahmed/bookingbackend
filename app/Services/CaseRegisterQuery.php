<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CasePriority;
use App\Enums\CaseStatus;
use App\Models\CaseModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Builds the filtered, paginated case register shared by the Dashboard and the
 * All Cases screen.
 *
 * The DepartmentScope global scope has already narrowed the result set to the
 * signed-in user's departments before any of these filters run.
 */
class CaseRegisterQuery
{
    /**
     * Applied filters, echoed back to the view so the controls keep their state.
     *
     * @return array{search: string, department: ?int, status: ?string, priority: ?string, high_only: bool, overdue: bool}
     */
    public static function filtersFrom(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('q', '')),
            'department' => $request->filled('department') ? (int) $request->query('department') : null,
            'status' => $request->filled('status') && CaseStatus::tryFrom((string) $request->query('status'))
                ? (string) $request->query('status')
                : null,
            'priority' => $request->filled('priority') && CasePriority::tryFrom((string) $request->query('priority'))
                ? (string) $request->query('priority')
                : null,
            'high_only' => $request->boolean('high_only'),
            'overdue' => $request->boolean('overdue'),
        ];
    }

    /**
     * @param  array{search: string, department: ?int, status: ?string, priority: ?string, high_only: bool, overdue: bool}  $filters
     * @return LengthAwarePaginator<int, CaseModel>
     */
    public function paginate(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        return $this->query($filters)
            ->paginate($perPage ?? (int) config('cases.per_page', 20))
            ->withQueryString();
    }

    /**
     * @param  array{search: string, department: ?int, status: ?string, priority: ?string, high_only: bool, overdue: bool}  $filters
     * @return Builder<CaseModel>
     */
    public function query(array $filters): Builder
    {
        return CaseModel::query()
            // Eager-loaded because the table renders department and logging
            // officer for every row; without this the list is an N+1.
            ->with(['department', 'creator', 'assignee'])
            ->search($filters['search'])
            ->when($filters['department'], fn (Builder $q, int $id) => $q->where('department_id', $id))
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'], fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['high_only'], fn (Builder $q) => $q->highPriority())
            ->when($filters['overdue'], fn (Builder $q) => $q->overdue())
            ->latest('created_at');
    }
}
