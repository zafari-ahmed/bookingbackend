@php
    use App\Models\Department;
    use App\Models\User;

    $canAddDepartment = auth()->user()->can('create', Department::class);
    $canAddUser = auth()->user()->can('create', User::class);
@endphp

<x-layouts::app title="Departments &amp; Users">
    <x-slot:actions>
        @if ($canAddDepartment)
            <button type="button" x-data @click="$dispatch('open-department-form')" class="btn-primary">
                <x-icon name="plus" class="size-4" />
                <span class="hidden sm:inline">Add Department</span>
            </button>
        @endif
    </x-slot:actions>

    <div class="grid gap-6">
        @if ($canAddDepartment)
            <section
                x-data="{ open: false }"
                @open-department-form.window="open = true"
                x-show="open || {{ $errors->hasAny(['name', 'color_tag', 'focal_person']) ? 'true' : 'false' }}"
                x-cloak
                class="card px-5 py-5 sm:px-6"
            >
                <div class="flex items-center justify-between gap-3 border-b border-border pb-4">
                    <h2 class="text-lg font-bold tracking-tight text-navy">Add Department</h2>
                    <button type="button" @click="open = false" class="btn-secondary">Close</button>
                </div>

                <form method="POST" action="{{ route('departments.store') }}" class="mt-5 grid gap-4">
                    @csrf

                    <div class="grid gap-4 lg:grid-cols-2">
                        <x-form-field name="name" label="Department Name" placeholder="e.g. Fisheries" required />
                        <x-form-field name="focal_person" label="Focal Person" placeholder="Officer responsible" />
                        <x-form-field
                            name="color_tag"
                            label="Colour Tag"
                            type="select"
                            placeholder-option="Select a tag"
                            :options="[
                                'protection' => 'Protection',
                                'services' => 'Services',
                                'enforcement' => 'Enforcement',
                                'welfare' => 'Welfare',
                                'civic' => 'Civic',
                            ]"
                            required
                        />
                        <x-form-field name="description" label="Description" placeholder="Scope of work" />
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center sm:w-auto sm:justify-self-end">
                        Add department
                    </button>
                </form>
            </section>
        @endif

        {{-- 3 columns desktop, 2 tablet, 1 mobile (design-system.md §7). --}}
        <section aria-label="Departments" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($departments as $department)
                @php $isSelected = $selected !== null && (int) $selected->id === (int) $department->id; @endphp

                <a
                    href="{{ $isSelected ? route('departments.index') : route('departments.show', $department) }}"
                    class="card px-5 py-5 transition hover:-translate-y-px hover:shadow-[0_6px_18px_rgba(19,42,69,0.06)] {{ $isSelected ? 'border-teal ring-1 ring-teal' : '' }}"
                >
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-control text-xs font-extrabold {{ $department->tagClasses() }}">
                            {{ $department->initials() }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-[15px] font-bold text-navy">{{ $department->name }}</h3>
                            <p class="meta mt-0.5 truncate">{{ $department->focal_person ?: 'No focal person set' }}</p>
                        </div>
                    </div>

                    @if ($department->description)
                        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-text-secondary">
                            {{ $department->description }}
                        </p>
                    @endif

                    <dl class="mt-4 flex items-center gap-6 border-t border-border pt-3.5">
                        <div>
                            <dt class="field-label">Users</dt>
                            <dd class="mt-0.5 text-lg font-extrabold text-navy">{{ $department->users_count }}</dd>
                        </div>
                        <div>
                            <dt class="field-label">Open Cases</dt>
                            <dd class="mt-0.5 text-lg font-extrabold text-navy">
                                {{ $openCaseCounts[$department->id] ?? 0 }}
                            </dd>
                        </div>
                        @unless ($department->is_active)
                            <span class="pill ml-auto bg-closed-bg text-closed-text">Inactive</span>
                        @endunless
                    </dl>
                </a>
            @endforeach
        </section>

        @if ($selected)
            <section aria-label="Department users" class="card px-5 py-5 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-navy">{{ $selected->name }} — Users</h2>
                        <p class="meta mt-0.5">
                            Officers with access to this department's cases. Users assigned elsewhere too show every tag.
                        </p>
                    </div>

                    @if ($canAddUser)
                        <a href="{{ route('users.create', ['department' => $selected->id]) }}" class="btn-primary">
                            <x-icon name="plus" class="size-4" /> Add User
                        </a>
                    @endif
                </div>

                <div class="mt-4 hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[820px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-3 py-3 text-left">Officer</th>
                                <th class="px-3 py-3 text-left">Role</th>
                                <th class="px-3 py-3 text-left">Departments</th>
                                <th class="px-3 py-3 text-left">Status</th>
                                <th class="px-3 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selected->users as $user)
                                <tr class="border-b border-border last:border-0 hover:bg-cream">
                                    <td class="px-3 py-3.5">
                                        <span class="font-semibold text-text-primary">{{ $user->name }}</span>
                                        <span class="meta block">{{ $user->email }}</span>
                                        @if ($user->designation)
                                            <span class="meta block">{{ $user->designation }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <span class="pill {{ $user->role->pillClasses() }}">{{ $user->role->label() }}</span>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($user->departments as $userDepartment)
                                                <span class="pill {{ $userDepartment->tagClasses() }}">
                                                    {{ $userDepartment->name }}@if ($userDepartment->pivot->is_primary) · Primary @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <span class="pill {{ $user->is_active ? 'bg-resolved-bg text-resolved-text' : 'bg-closed-bg text-closed-text' }}">
                                            {{ $user->is_active ? 'Active' : 'Deactivated' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3.5">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('update', $user)
                                                <a href="{{ route('users.edit', $user) }}" class="btn-secondary text-xs">
                                                    Edit
                                                </a>
                                            @endcan

                                            @can('toggleActive', $user)
                                                <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-secondary text-xs">
                                                        {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                                    </button>
                                                </form>
                                            @endcan

                                            @can('manageUsers', $selected)
                                                @if ($user->departments->count() > 1)
                                                    <form method="POST" action="{{ route('users.departments.revoke', [$user, $selected]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-secondary text-xs">Revoke</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-12 text-center text-sm text-text-muted">
                                        No users assigned to this department yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile: stacked cards. --}}
                <div class="mt-4 divide-y divide-border md:hidden">
                    @forelse ($selected->users as $user)
                        <div class="py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-text-primary">{{ $user->name }}</p>
                                    <p class="meta truncate">{{ $user->email }}</p>
                                </div>
                                <span class="pill shrink-0 {{ $user->is_active ? 'bg-resolved-bg text-resolved-text' : 'bg-closed-bg text-closed-text' }}">
                                    {{ $user->is_active ? 'Active' : 'Off' }}
                                </span>
                            </div>

                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                <span class="pill {{ $user->role->pillClasses() }}">{{ $user->role->label() }}</span>
                                @foreach ($user->departments as $userDepartment)
                                    <span class="pill {{ $userDepartment->tagClasses() }}">
                                        {{ $userDepartment->name }}@if ($userDepartment->pivot->is_primary) · Primary @endif
                                    </span>
                                @endforeach
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" class="btn-secondary flex-1 justify-center text-xs">
                                        Edit
                                    </a>
                                @endcan

                                @can('toggleActive', $user)
                                    <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn-secondary w-full justify-center text-xs">
                                            {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-text-muted">No users assigned to this department yet.</p>
                    @endforelse
                </div>

            </section>
        @else
            <p class="card px-5 py-12 text-center text-sm text-text-muted">
                Select a department above to see the officers assigned to it.
            </p>
        @endif
    </div>
</x-layouts::app>
