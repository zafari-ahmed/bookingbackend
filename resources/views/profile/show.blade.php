<x-layouts::app title="My Profile">
    <div class="mx-auto grid w-full max-w-[880px] gap-6">
        <section class="card flex flex-wrap items-center gap-4 px-5 py-5 sm:px-7">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-control bg-teal-tint text-sm font-extrabold text-teal">
                {{ $user->initials() }}
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="truncate text-lg font-bold tracking-tight text-navy">{{ $user->name }}</h2>
                <p class="meta mt-0.5 truncate">{{ $user->contextLabel() }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="pill {{ $user->role->pillClasses() }}">{{ $user->role->label() }}</span>
                <span class="meta">
                    {{ $user->last_login_at !== null
                        ? 'Last signed in '.$user->last_login_at->diffForHumans()
                        : 'Has not signed in yet' }}
                </span>
            </div>
        </section>

        <form method="POST" action="{{ route('profile.update') }}" class="grid gap-6">
            @csrf
            @method('PATCH')

            <section class="card px-5 py-6 sm:px-7">
                <header class="border-b border-border pb-4">
                    <h2 class="text-lg font-bold tracking-tight text-navy">Personal details</h2>
                    <p class="mt-1 text-sm text-text-secondary">
                        Your name appears on remarks, routing steps and the activity log.
                    </p>
                </header>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <x-form-field
                        name="name"
                        label="Full Name"
                        :value="$user->name"
                        required
                    />

                    <x-form-field
                        name="phone"
                        label="Phone"
                        type="tel"
                        :value="$user->phone"
                        placeholder="0300-0000000"
                        mono
                    />

                    <x-form-field
                        name="email"
                        label="Email Address"
                        type="email"
                        :value="$user->email"
                        hint="Contact an administrator to change this address."
                        readonly
                    />

                    <x-form-field
                        name="designation"
                        label="Designation"
                        :value="$user->designation ?: 'Not set'"
                        hint="Contact an administrator to update your designation."
                        readonly
                    />
                </div>
            </section>

            @if ($canChoosePrimary)
                <section class="card px-5 py-6 sm:px-7">
                    <header class="border-b border-border pb-4">
                        <h2 class="text-lg font-bold tracking-tight text-navy">Primary department</h2>
                        <p class="mt-1 text-sm text-text-secondary">
                            Used as the default when you log a case or record a remark. This does not change which departments you can access.
                        </p>
                    </header>

                    <div class="mt-5 grid gap-4 lg:grid-cols-2">
                        <x-form-field
                            name="primary_department_id"
                            label="Primary Department"
                            type="select"
                            :options="$user->departments->sortBy('name')->pluck('name', 'id')"
                            :value="$user->primaryDepartment()?->id"
                            required
                        />
                    </div>
                </section>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button type="submit" class="btn-primary w-full justify-center sm:w-auto">
                    Save profile
                </button>
            </div>
        </form>

        <section class="card px-5 py-6 sm:px-7">
            <header class="border-b border-border pb-4">
                <h2 class="text-lg font-bold tracking-tight text-navy">Change password</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    Leave this blank unless you want a new password. You will need the current one to confirm.
                </p>
            </header>

            <form method="POST" action="{{ route('profile.password') }}" class="mt-5 grid gap-4 lg:grid-cols-2">
                @csrf
                @method('PATCH')

                <x-form-field
                    name="current_password"
                    label="Current Password"
                    type="password"
                    required
                />

                <span class="hidden lg:block" aria-hidden="true"></span>

                <x-form-field
                    name="password"
                    label="New Password"
                    type="password"
                    required
                />

                <x-form-field
                    name="password_confirmation"
                    label="Confirm New Password"
                    type="password"
                    required
                />

                <div class="lg:col-span-2 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button type="submit" class="btn-primary w-full justify-center sm:w-auto">
                        Update password
                    </button>
                </div>
            </form>
        </section>

        <section class="card px-5 py-6 sm:px-7">
            <header class="border-b border-border pb-4">
                <h2 class="text-lg font-bold tracking-tight text-navy">Two-factor authentication</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    A second factor protects this account if the password is ever compromised.
                </p>
            </header>

            <div class="mt-5">
                <x-two-factor-panel
                    :enabled="$enabled"
                    :confirmed="$confirmed"
                    :qr-code-svg="$qrCodeSvg"
                    :recovery-codes="$recoveryCodes"
                    :can-disable="$canDisableTwoFactor"
                />
            </div>
        </section>
    </div>
</x-layouts::app>
