<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\DepartmentDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly DepartmentDirectory $directory) {}

    /**
     * AC office directory of every account — search, reset, activate, demote.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewDirectory', User::class);

        $filters = [
            'search' => trim((string) $request->query('q', '')),
            'role' => UserRole::tryFrom((string) $request->query('role'))?->value,
            'department' => $request->filled('department') ? (int) $request->query('department') : null,
            'status' => in_array($request->query('status'), ['active', 'inactive'], true)
                ? (string) $request->query('status')
                : null,
        ];

        $users = User::query()
            ->with('departments')
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['search']).'%';
                $query->where(function ($inner) use ($like): void {
                    $inner
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('designation', 'like', $like);
                });
            })
            ->when($filters['role'] !== 'super_admin', function ($query) use ($filters): void {
                $query->where('role', '!=', UserRole::SuperAdmin->value);
            })
            ->when($filters['role'], fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['department'], fn ($query, int $id) => $query->whereHas(
                'departments',
                fn ($departments) => $departments->whereKey($id),
            ))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate((int) config('cases.per_page', 20))
            ->withQueryString();

        $counts = User::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->selectRaw("SUM(CASE WHEN role = '".UserRole::DepartmentAdmin->value."' THEN 1 ELSE 0 END) as admins")
            ->when($filters['role'] !== 'super_admin', function ($query) use ($filters): void {
                $query->where('role', '!=', UserRole::SuperAdmin->value);
            })
            ->first();

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'departments' => $this->directory->active(),
            'roles' => UserRole::options(),
            'counts' => [
                'total' => (int) $counts->total,
                'active' => (int) $counts->active,
                'inactive' => (int) $counts->inactive,
                'admins' => (int) $counts->admins,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $actor = $request->user();
        $grantable = $this->directory->availableTo($actor);

        // The admin arrives from a department's user list, so that department is
        // ticked and set as primary before they type anything.
        $context = $grantable->firstWhere('id', (int) $request->query('department'))
            ?? ($actor->isSuperAdmin() ? null : $grantable->first());

        return view('users.create', [
            'grantableDepartments' => $grantable,
            'contextDepartment' => $context,
            'assignableRoles' => $this->assignableRoles($actor),
            'returnUrl' => $this->returnUrl($actor, $context),
        ]);
    }

    /**
     * Accounts are provisioned here, never by self-registration.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'designation' => $data['designation'] ?? null,
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
            'is_active' => true,
        ]);

        // The provisioning admin vouches for the address, so the account is
        // usable immediately rather than waiting on a verification email that
        // would otherwise strand it behind the `verified` middleware. Set here
        // rather than above because email_verified_at is not mass-assignable.
        $user->markEmailAsVerified();

        $this->syncDepartments(
            $user,
            $data['departments'] ?? [],
            $data['primary_department_id'] ?? null,
        );

        return $this->backToDirectory($request->user(), $user)
            ->with('status', $user->name.' now has access to the case register.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorize('update', $user);

        $actor = $request->user();
        $user->load('departments');

        return view('users.edit', [
            'user' => $user,
            'grantableDepartments' => $this->directory->availableTo($actor),
            'assignableRoles' => $this->assignableRoles($actor),
            'canChangeRole' => $actor->can('changeRole', $user),
            'canToggleActive' => $actor->can('toggleActive', $user),
            'returnUrl' => $this->returnUrl($actor, $user->primaryDepartment()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'designation' => $data['designation'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        // Both of these are dropped by the form request when the actor is not
        // allowed to set them, so the absence of a key is the authorisation.
        if (array_key_exists('is_active', $data)) {
            $user->is_active = (bool) $data['is_active'];
        }

        if (array_key_exists('role', $data)) {
            $user->role = UserRole::from($data['role']);
        }

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();

        if (array_key_exists('departments', $data)) {
            $this->syncDepartments(
                $user,
                $data['departments'] ?? [],
                $data['primary_department_id'] ?? null,
                $this->departmentsBeyond($request->user(), $user),
            );
        }

        return $this->backToDirectory($request->user(), $user)
            ->with('status', $user->name."'s account has been updated.");
    }

    /**
     * Deactivation rather than deletion — comments and routing history stay
     * attributed to the officer who recorded them.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', sprintf(
            '%s has been %s.',
            $user->name,
            $user->is_active ? 'reactivated' : 'deactivated',
        ));
    }

    /**
     * Drop a department administrator back to an ordinary officer. Super-admin
     * accounts are reassigned through Edit so they are never left with no
     * department and no role.
     */
    public function revokeRole(Request $request, User $user): RedirectResponse
    {
        $this->authorize('revokeRole', $user);

        if ($user->isSuperAdmin()) {
            return back()->withErrors([
                'role' => 'Reassign this account to a department from Edit before changing its role.',
            ]);
        }

        if ($user->role === UserRole::DepartmentUser) {
            return back()->with('status', $user->name.' is already a department user.');
        }

        $user->update(['role' => UserRole::DepartmentUser]);

        return back()->with('status', $user->name.' is now a department user.');
    }

    /**
     * Revokes access to one department without touching the user's other
     * department assignments.
     */
    public function revokeDepartment(Request $request, User $user, Department $department): RedirectResponse
    {
        $this->authorize('manageUsers', $department);

        $user->departments()->detach($department->getKey());

        return back()->with('status', sprintf(
            '%s no longer has access to %s.',
            $user->name,
            $department->name,
        ));
    }

    /**
     * A department admin may only hand out the role they hold beneath them.
     *
     * @return array<string, string>
     */
    private function assignableRoles(User $actor): array
    {
        return $actor->isSuperAdmin()
            ? UserRole::options()
            : [UserRole::DepartmentUser->value => UserRole::DepartmentUser->label()];
    }

    /**
     * Department grants the actor was never shown, and so cannot have meant to
     * withdraw. Without this a department admin saving the edit form would
     * silently revoke a multi-department officer's access everywhere else.
     *
     * @return array<int, int>
     */
    private function departmentsBeyond(User $actor, User $target): array
    {
        $grantable = $this->directory->availableTo($actor)
            ->map(fn (Department $department): int => (int) $department->id)
            ->all();

        return $target->departments
            ->map(fn (Department $department): int => (int) $department->id)
            ->reject(fn (int $id): bool => in_array($id, $grantable, true))
            ->values()
            ->all();
    }

    /**
     * Super admin lands back on the global directory; a department admin
     * returns to the department they were working in.
     */
    private function backToDirectory(User $actor, User $target): RedirectResponse
    {
        if ($actor->isSuperAdmin()) {
            return redirect()->route('users.index');
        }

        $department = $target->load('departments')->departments
            ->first(fn (Department $department): bool => $actor->can('view', $department));

        return redirect()->route(
            $department !== null ? 'departments.show' : 'departments.index',
            $department !== null ? ['department' => $department] : [],
        );
    }

    private function returnUrl(User $actor, ?Department $context): string
    {
        if ($actor->isSuperAdmin()) {
            return route('users.index');
        }

        return $context !== null && $actor->can('view', $context)
            ? route('departments.show', $context)
            : route('departments.index');
    }

    /**
     * @param  array<int, int|string>  $departmentIds
     * @param  array<int, int>  $retainIds
     */
    private function syncDepartments(
        User $user,
        array $departmentIds,
        int|string|null $primaryId,
        array $retainIds = [],
    ): void {
        $departmentIds = array_values(array_unique(
            array_merge(array_map('intval', $departmentIds), $retainIds)
        ));

        $primaryId = $primaryId !== null ? (int) $primaryId : null;

        // Exactly one department is the "home" department used for UI defaults.
        if (! in_array($primaryId, $departmentIds, true)) {
            $primaryId = $departmentIds[0] ?? null;
        }

        $user->departments()->sync(
            collect($departmentIds)
                ->mapWithKeys(fn (int $id): array => [
                    $id => [
                        'is_primary' => $id === $primaryId,
                        'created_at' => Carbon::now(),
                    ],
                ])
                ->all()
        );

        $user->unsetRelation('departments');
    }
}
