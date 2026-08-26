<x-layouts::app
    :title="'Edit '.$user->name"
    :back-link="$returnUrl"
    :back-label="auth()->user()->isSuperAdmin() ? 'Back to Users' : 'Back to Departments'"
>
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
                <span class="pill {{ $user->is_active ? 'bg-resolved-bg text-resolved-text' : 'bg-closed-bg text-closed-text' }}">
                    {{ $user->is_active ? 'Active' : 'Deactivated' }}
                </span>
                <span class="meta">
                    {{ $user->last_login_at !== null
                        ? 'Last signed in '.$user->last_login_at->diffForHumans()
                        : 'Has not signed in yet' }}
                </span>
            </div>
        </section>

        <x-user-form
            :action="route('users.update', $user)"
            method="PATCH"
            :user="$user"
            :departments="$grantableDepartments"
            :roles="$assignableRoles"
            :can-change-role="$canChangeRole"
            :can-toggle-active="$canToggleActive"
            :cancel-url="$returnUrl"
            submit-label="Save changes"
        />
    </div>
</x-layouts::app>
