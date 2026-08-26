<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\CaseModel;
use App\Models\Department;
use App\Models\User;
use App\Policies\CasePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureGates();
        $this->configureViews();

        Paginator::defaultView('pagination::tailwind');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    private function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::unguard(false);
    }

    private function configureGates(): void
    {
        Gate::policy(CaseModel::class, CasePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('manage-departments', fn (User $user): bool => $user->isSuperAdmin() || $user->isDepartmentAdmin());
        Gate::define('manage-all-users', fn (User $user): bool => $user->isSuperAdmin());
    }

    /**
     * Layouts live in resources/views/layouts per the agreed project
     * structure, so they are registered as a namespaced anonymous component
     * path rather than being moved under views/components.
     */
    private function configureViews(): void
    {
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
    }
}
