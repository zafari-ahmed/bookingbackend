@props([
    'action',
    'method' => 'POST',
    'user' => null,
    'departments',
    'roles' => [],
    'contextDepartment' => null,
    'canChangeRole' => true,
    'canToggleActive' => false,
    'submitLabel' => 'Save',
    'cancelUrl' => null,
])

@php
    $isEdit = $user !== null;

    $currentDepartmentIds = $isEdit
        ? $user->departments->map(fn ($department) => (string) $department->id)->all()
        : array_filter([$contextDepartment?->id === null ? null : (string) $contextDepartment->id]);

    $checkedDepartmentIds = array_map(
        'strval',
        (array) old('departments', $currentDepartmentIds),
    );

    // A retained grant sits outside the tick list but must stay selectable as
    // the home department, otherwise saving would quietly move it.
    $primaryOptions = $departments
        ->concat($isEdit ? $user->departments : [])
        ->unique('id')
        ->sortBy('name')
        ->pluck('name', 'id');

    $currentPrimaryId = $isEdit
        ? $user->primaryDepartment()?->id
        : $contextDepartment?->id;

    $defaultRole = $isEdit
        ? $user->role->value
        : \App\Enums\UserRole::DepartmentUser->value;

    // @error has no wildcard form, and a rejection lands on departments.0
    // rather than departments, so both shapes are flattened together here.
    $departmentErrors = collect($errors->get('departments'))
        ->merge(\Illuminate\Support\Arr::flatten($errors->get('departments.*')))
        ->unique()
        ->all();
@endphp

<form method="POST" action="{{ $action }}" class="mx-auto grid w-full max-w-[880px] gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="card px-5 py-6 sm:px-7">
        <header class="border-b border-border pb-4">
            <h2 class="text-lg font-bold tracking-tight text-navy">Officer Information</h2>
            <p class="mt-1 text-sm text-text-secondary">
                The name and designation shown against every remark, routing step and activity entry this officer records.
            </p>
        </header>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <x-form-field
                name="name"
                label="Full Name"
                :value="$user?->name"
                placeholder="e.g. Farhan Ahmed Shaikh"
                required
            />

            <x-form-field
                name="email"
                label="Email Address"
                type="email"
                :value="$user?->email"
                placeholder="officer@sindh.gov.pk"
                hint="Used to sign in and to receive case notifications."
                required
            />

            <x-form-field
                name="designation"
                label="Designation"
                :value="$user?->designation"
                placeholder="e.g. Assistant Director"
            />

            <x-form-field
                name="phone"
                label="Phone"
                type="tel"
                :value="$user?->phone"
                placeholder="0300-0000000"
                mono
            />
        </div>
    </section>

    <section class="card px-5 py-6 sm:px-7">
        <header class="border-b border-border pb-4">
            <h2 class="text-lg font-bold tracking-tight text-navy">
                {{ $isEdit ? 'Reset Password' : 'Sign-in Password' }}
            </h2>
            <p class="mt-1 text-sm text-text-secondary">
                {{ $isEdit
                    ? 'Leave both fields blank to keep the officer\'s current password.'
                    : 'Share this with the officer directly; they can change it after their first sign-in.' }}
            </p>
        </header>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <x-form-field
                name="password"
                label="{{ $isEdit ? 'New Password' : 'Password' }}"
                type="password"
                :required="! $isEdit"
            />

            <x-form-field
                name="password_confirmation"
                label="Confirm Password"
                type="password"
                :required="! $isEdit"
            />
        </div>
    </section>

    <section class="card px-5 py-6 sm:px-7">
        <header class="border-b border-border pb-4">
            <h2 class="text-lg font-bold tracking-tight text-navy">Access &amp; Rights</h2>
            <p class="mt-1 text-sm text-text-secondary">
                The role sets what this officer may do; the department list sets which cases they may see at all.
            </p>
        </header>

        <div class="mt-5 grid gap-5">
            <div class="grid gap-4 lg:grid-cols-2">
                @if ($canChangeRole)
                    <x-form-field
                        name="role"
                        label="Role"
                        type="select"
                        :options="$roles"
                        :value="$defaultRole"
                        hint="Department admins can provision officers and re-route cases."
                        required
                    />
                @else
                    <div class="grid gap-1.5">
                        <span class="field-label">Role</span>
                        <div class="flex min-h-11 items-center gap-2.5 rounded-control border border-border bg-cream px-3.5">
                            <span class="pill {{ $user->role->pillClasses() }}">{{ $user->role->label() }}</span>
                        </div>
                        <p class="meta">Only the AC office may change a role.</p>
                    </div>
                @endif

                @if ($canToggleActive)
                    <x-form-field
                        name="is_active"
                        label="Account Status"
                        type="select"
                        :options="['1' => 'Active', '0' => 'Deactivated']"
                        :value="$user->is_active ? '1' : '0'"
                        hint="Deactivated officers keep their history but cannot sign in."
                        required
                    />
                @elseif ($isEdit)
                    <div class="grid gap-1.5">
                        <span class="field-label">Account Status</span>
                        <div class="flex min-h-11 items-center rounded-control border border-border bg-cream px-3.5">
                            <span class="pill {{ $user->is_active ? 'bg-resolved-bg text-resolved-text' : 'bg-closed-bg text-closed-text' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </div>
                        <p class="meta">You cannot change this account's status.</p>
                    </div>
                @endif
            </div>

            <fieldset class="grid gap-2">
                <legend class="field-label">Department Access</legend>
                <p class="meta">
                    Tick more than one to give this officer multi-department access.
                    Super admins see every department without a grant.
                </p>

                <div class="mt-1 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($departments as $department)
                        <label class="flex min-h-11 items-center gap-2.5 rounded-control border border-border bg-cream px-3.5 text-[13.5px] font-semibold text-text-secondary">
                            <input
                                type="checkbox"
                                name="departments[]"
                                value="{{ $department->id }}"
                                @checked(in_array((string) $department->id, $checkedDepartmentIds, true))
                                class="size-4 rounded border-border text-teal focus:ring-teal"
                            >
                            {{ $department->name }}
                        </label>
                    @endforeach
                </div>

                @foreach ($departmentErrors as $departmentError)
                    <p class="field-error">{{ $departmentError }}</p>
                @endforeach

                @if ($isEdit)
                    @php
                        $retained = $user->departments->reject(
                            fn ($department) => $departments->contains('id', $department->id)
                        );
                    @endphp

                    @if ($retained->isNotEmpty())
                        <p class="mt-1 rounded-control border border-border bg-cream px-3.5 py-2.5 text-xs text-text-muted">
                            This officer also has access to
                            {{ $retained->pluck('name')->join(', ', ' and ') }}, which
                            {{ $retained->count() === 1 ? 'is' : 'are' }} outside your remit. Saving will leave
                            {{ $retained->count() === 1 ? 'it' : 'them' }} untouched.
                        </p>
                    @endif
                @endif
            </fieldset>

            <div class="grid gap-4 lg:grid-cols-2">
                <x-form-field
                    name="primary_department_id"
                    label="Primary Department"
                    type="select"
                    :options="$primaryOptions"
                    :value="$currentPrimaryId"
                    hint="Their home department — used as the default on new cases and remarks."
                />
            </div>
        </div>
    </section>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
        <a href="{{ $cancelUrl ?? route('departments.index') }}" class="btn-secondary w-full justify-center sm:w-auto">
            Cancel
        </a>
        <button type="submit" class="btn-primary w-full justify-center sm:w-auto">{{ $submitLabel }}</button>
    </div>
</form>
