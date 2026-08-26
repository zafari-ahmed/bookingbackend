<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DepartmentPolicy
{
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

    /**
     * Department & User Management is visible to department admins, scoped to
     * the departments they run; everyone else is redirected away.
     */
    public function viewAny(User $user): bool
    {
        return $user->isDepartmentAdmin();
    }

    public function view(User $user, Department $department): bool
    {
        return $user->isDepartmentAdmin() && $user->belongsToDepartment($department->id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Department $department): bool
    {
        return false;
    }

    public function delete(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Adding or removing officers within a department the admin runs.
     */
    public function manageUsers(User $user, Department $department): bool
    {
        return $user->isDepartmentAdmin() && $user->belongsToDepartment($department->id);
    }
}
