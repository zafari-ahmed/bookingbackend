<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Second line of defence behind the DepartmentScope global scope. The scope
 * stops other departments' cases from appearing in a result set; this policy
 * stops a directly addressed case from being acted on.
 */
class CasePolicy
{
    /**
     * A deactivated account keeps its history but loses every ability.
     */
    public function before(User $user, string $ability): ?Response
    {
        if (! $user->is_active) {
            return Response::deny('This account has been deactivated by the AC office.');
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CaseModel $case): bool
    {
        return $this->hasDepartmentAccess($user, $case);
    }

    /**
     * Anyone with a department can log a walk-in complaint at the AC desk.
     */
    public function create(User $user): bool
    {
        return $user->accessibleDepartmentIds() !== [];
    }

    public function update(User $user, CaseModel $case): bool
    {
        return $this->hasDepartmentAccess($user, $case);
    }

    public function comment(User $user, CaseModel $case): bool
    {
        return $this->hasDepartmentAccess($user, $case);
    }

    public function uploadAttachment(User $user, CaseModel $case): bool
    {
        return $this->hasDepartmentAccess($user, $case);
    }

    /**
     * Routing a case onward is a supervisory act, so plain department users
     * may comment but not re-route.
     */
    public function route(User $user, CaseModel $case): bool
    {
        return $user->isDepartmentAdmin() && $this->hasDepartmentAccess($user, $case);
    }

    /**
     * Cases are legal records: only the AC office (super_admin, handled in
     * `before`) may soft-delete one.
     */
    public function delete(User $user, CaseModel $case): bool
    {
        return false;
    }

    /**
     * A case is reachable when it currently sits with one of the user's
     * departments, or passed through one of them earlier in its routing.
     */
    private function hasDepartmentAccess(User $user, CaseModel $case): bool
    {
        $departmentIds = $user->accessibleDepartmentIds();

        if ($departmentIds === []) {
            return false;
        }

        if (in_array((int) $case->department_id, $departmentIds, true)) {
            return true;
        }

        return $case->routingSteps()
            ->whereIn('department_id', $departmentIds)
            ->exists();
    }
}
