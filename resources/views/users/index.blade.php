@php
    use App\Enums\UserRole;
@endphp

<x-layouts::app title="Users">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn-primary">
            <x-icon name="user-plus" class="size-4" />
            <span class="hidden sm:inline">Add User</span>
        </a>
    </x-slot:actions>

    <div class="grid gap-4">
        <section aria-label="Account totals" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="All Accounts" :value="$counts['total']" tone="total" :href="route('users.index')" />
            <x-stat-card label="Active" :value="$counts['active']" tone="resolved" :href="route('users.index', ['status' => 'active'])" />
            <x-stat-card label="Deactivated" :value="$counts['inactive']" tone="overdue" :href="route('users.index', ['status' => 'inactive'])" />
            <x-stat-card label="Department Admins" :value="$counts['admins']" tone="pending" :href="route('users.index', ['role' => UserRole::DepartmentAdmin->value])" />
        </section>

        <form method="GET" action="{{ route('users.index') }}" class="card px-4 py-4 sm:px-5">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))_auto]">
                <div class="relative">
                    <label for="user-q" class="sr-only">Search officers</label>
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted">
                        <x-icon name="search" class="size-4" />
                    </span>
                    <input id="user-q" type="search" name="q" value="{{ $filters['search'] }}"
                           placeholder="Name, email, phone or designation"
                           class="field pl-10">
                </div>

                <div>
                    <label for="user-role" class="sr-only">Role</label>
                    <select id="user-role" name="role" class="field">
                        <option value="">All roles</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="user-department" class="sr-only">Department</label>
                    <select id="user-department" name="department" class="field">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected($filters['department'] === (int) $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="user-status" class="sr-only">Status</label>
                    <select id="user-status" name="status" class="field">
                        <option value="">All statuses</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Deactivated</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary w-full xl:w-auto">Filter</button>
                    @if (array_filter($filters))
                        <a href="{{ route('users.index') }}" class="btn-secondary w-full xl:w-auto">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-bold tracking-tight text-navy">All Users</h2>
            <p class="meta">
                Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ number_format($users->total()) }}
            </p>
        </div>

        <div class="card overflow-hidden">
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[980px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-5 py-3.5 text-left">Officer</th>
                            <th class="px-4 py-3.5 text-left">Role</th>
                            <th class="px-4 py-3.5 text-left">Departments</th>
                            <th class="px-4 py-3.5 text-left">Status</th>
                            <th class="px-4 py-3.5 text-left">Last sign-in</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $officer)
                            <tr class="border-b border-border last:border-0 hover:bg-cream">
                                <td class="px-5 py-3.5">
                                    <span class="font-semibold text-text-primary">{{ $officer->name }}</span>
                                    <span class="meta block">{{ $officer->email }}</span>
                                    <span class="meta block">
                                        {{ $officer->designation ?: 'No designation' }}
                                        @if ($officer->phone)
                                            · {{ $officer->phone }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="pill {{ $officer->role->pillClasses() }}">{{ $officer->role->label() }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    @if ($officer->isSuperAdmin())
                                        <span class="meta">All departments</span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse ($officer->departments as $department)
                                                <span class="pill {{ $department->tagClasses() }}">
                                                    {{ $department->name }}@if ($department->pivot->is_primary) · Primary @endif
                                                </span>
                                            @empty
                                                <span class="meta">Unassigned</span>
                                            @endforelse
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="pill {{ $officer->is_active ? 'bg-resolved-bg text-resolved-text' : 'bg-closed-bg text-closed-text' }}">
                                        {{ $officer->is_active ? 'Active' : 'Deactivated' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="meta">
                                        {{ $officer->last_login_at?->format('d M Y, g:i A') ?? 'Never' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        @can('update', $officer)
                                            <a href="{{ route('users.edit', $officer) }}" class="btn-secondary text-xs">
                                                Edit
                                            </a>
                                        @endcan

                                        @can('toggleActive', $officer)
                                            <form method="POST" action="{{ route('users.toggle-active', $officer) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn-secondary text-xs">
                                                    {{ $officer->is_active ? 'Deactivate' : 'Reactivate' }}
                                                </button>
                                            </form>
                                        @endcan

                                        @can('revokeRole', $officer)
                                            @if ($officer->role === UserRole::DepartmentAdmin)
                                                <form method="POST" action="{{ route('users.revoke-role', $officer) }}"
                                                      onsubmit="return confirm('Revoke administrator rights for {{ $officer->name }}? They will remain a department user.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-secondary text-xs">
                                                        Revoke role
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center text-sm text-text-muted">
                                    No accounts match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-border md:hidden">
                @forelse ($users as $officer)
                    <div class="px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-text-primary">{{ $officer->name }}</p>
                                <p class="meta truncate">{{ $officer->email }}</p>
                                @if ($officer->phone)
                                    <p class="meta font-mono">{{ $officer->phone }}</p>
                                @endif
                            </div>
                            <span class="pill shrink-0 {{ $officer->is_active ? 'bg-resolved-bg text-resolved-text' : 'bg-closed-bg text-closed-text' }}">
                                {{ $officer->is_active ? 'Active' : 'Off' }}
                            </span>
                        </div>

                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <span class="pill {{ $officer->role->pillClasses() }}">{{ $officer->role->label() }}</span>
                            @if ($officer->isSuperAdmin())
                                <span class="meta self-center">All departments</span>
                            @else
                                @foreach ($officer->departments as $department)
                                    <span class="pill {{ $department->tagClasses() }}">{{ $department->name }}</span>
                                @endforeach
                            @endif
                        </div>

                        <p class="meta mt-2">
                            Last sign-in {{ $officer->last_login_at?->diffForHumans() ?? 'never' }}
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @can('update', $officer)
                                <a href="{{ route('users.edit', $officer) }}" class="btn-secondary flex-1 justify-center text-xs">
                                    Edit
                                </a>
                            @endcan

                            @can('toggleActive', $officer)
                                <form method="POST" action="{{ route('users.toggle-active', $officer) }}" class="flex-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-secondary w-full justify-center text-xs">
                                        {{ $officer->is_active ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            @endcan

                            @can('revokeRole', $officer)
                                @if ($officer->role === UserRole::DepartmentAdmin)
                                    <form method="POST" action="{{ route('users.revoke-role', $officer) }}" class="w-full"
                                          onsubmit="return confirm('Revoke administrator rights for {{ $officer->name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-secondary w-full justify-center text-xs">
                                            Revoke role
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-14 text-center text-sm text-text-muted">No accounts match the current filters.</p>
                @endforelse
            </div>

            @if ($users->hasPages())
                <div class="border-t border-border px-5 py-3.5">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
