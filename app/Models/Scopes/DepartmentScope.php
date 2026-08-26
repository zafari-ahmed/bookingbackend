<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

/**
 * Confines every case query to the departments the signed-in user belongs to.
 *
 * This is the application's primary defence against cross-department data
 * leakage. It is applied at the model level rather than per controller so that
 * a forgotten `where()` clause cannot silently expose another department's
 * complainants — including through relationship loads and eager loads.
 *
 * A user sees a case when either:
 *   - the case currently sits with one of their departments, or
 *   - the case was routed through one of their departments at some point.
 *
 * `super_admin` accounts bypass the scope entirely, as does any query run
 * without an authenticated user (console commands, queued jobs, seeders).
 */
class DepartmentScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user instanceof User || $user->isSuperAdmin()) {
            return;
        }

        $departmentIds = $user->accessibleDepartmentIds();
        $table = $model->getTable();

        if ($departmentIds === []) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where(function (Builder $query) use ($departmentIds, $table): void {
            $query
                ->whereIn($table.'.department_id', $departmentIds)
                ->orWhereExists(function (QueryBuilder $routing) use ($departmentIds, $table): void {
                    $routing
                        ->selectRaw('1')
                        ->from('case_routing')
                        ->whereColumn('case_routing.case_id', $table.'.id')
                        ->whereIn('case_routing.department_id', $departmentIds);
                });
        });
    }

    /**
     * Allow a deliberate, auditable escape hatch for system-level reporting.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('acrossAllDepartments', function (Builder $builder): Builder {
            return $builder->withoutGlobalScope(self::class);
        });
    }

    public static function appliesTo(?Authenticatable $user): bool
    {
        return $user instanceof User && ! $user->isSuperAdmin();
    }
}
