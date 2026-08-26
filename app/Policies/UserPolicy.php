<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function before(User $user, string $ability, mixed ...$arguments): ?Response
    {
        if (! $user->is_active) {
            return Response::deny('This account has been deactivated by the AC office.');
        }

        $target = $arguments[0] ?? null;

        if ($target instanceof User && in_array($ability, ['toggleActive', 'changeRole', 'revokeRole'], true)) {
            if ($user->is($target)) {
                return Response::deny('You cannot change your own access this way.');
            }

            if ($this->isSoleActiveSuperAdmin($target)) {
                return Response::deny('The AC office must keep at least one active super admin.');
            }
        }

        if ($user->isSuperAdmin()) {
            return Response::allow();
        }

        return null;
    }

    /**
     * The global user directory is an AC-office screen. Department admins
     * provision officers from the department they run, not from this list.
     */
    public function viewDirectory(User $user): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDepartmentAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isDepartmentAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isDepartmentAdmin() && $this->sharesDepartment($user, $target);
    }

    /**
     * Role changes are a privilege-escalation surface, so they stay with the
     * super_admin resolved in `before()`.
     */
    public function changeRole(User $user, User $target): bool
    {
        return false;
    }

    /**
     * Drop an administrator back to a department user without touching the
     * rest of their account. Super admins are reassigned through Edit.
     */
    public function revokeRole(User $user, User $target): bool
    {
        return false;
    }

    /**
     * A department admin may not lock themselves out, nor touch a super_admin.
     */
    public function toggleActive(User $user, User $target): bool
    {
        if ($user->is($target) || $target->role === UserRole::SuperAdmin) {
            return false;
        }

        return $user->isDepartmentAdmin() && $this->sharesDepartment($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    private function sharesDepartment(User $user, User $target): bool
    {
        return array_intersect(
            $user->accessibleDepartmentIds(),
            $target->accessibleDepartmentIds()
        ) !== [];
    }

    private function isSoleActiveSuperAdmin(User $target): bool
    {
        if (! $target->isSuperAdmin() || ! $target->is_active) {
            return false;
        }

        return ! User::query()
            ->active()
            ->where('role', UserRole::SuperAdmin)
            ->whereKeyNot($target->getKey())
            ->exists();
    }
}
