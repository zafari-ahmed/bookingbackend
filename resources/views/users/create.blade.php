<x-layouts::app
    title="Add User"
    :back-link="$returnUrl"
    :back-label="auth()->user()->isSuperAdmin() ? 'Back to Users' : ($contextDepartment !== null ? 'Back to '.$contextDepartment->name : 'Back to Departments')"
>
    <x-user-form
        :action="route('users.store')"
        :departments="$grantableDepartments"
        :roles="$assignableRoles"
        :context-department="$contextDepartment"
        :cancel-url="$returnUrl"
        submit-label="Create user"
    />
</x-layouts::app>
