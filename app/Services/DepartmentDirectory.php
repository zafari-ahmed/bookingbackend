<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The department picker appears in the top bar filters, the New Case form and
 * the forward control on every Case Detail page. Departments change perhaps
 * once a year, so this is read from cache rather than re-queried each time.
 */
class DepartmentDirectory
{
    public const CACHE_KEY = 'departments:active';

    /**
     * Every active department (used by super admins and by the "forward to"
     * picker, which may target any department).
     *
     * Rows are cached as plain arrays and rehydrated: serialised Eloquent
     * models in a shared cache store break the moment the model gains a cast
     * or an attribute, and come back as incomplete objects.
     *
     * @return Collection<int, Department>
     */
    public function active(): Collection
    {
        $rows = Cache::remember(
            self::CACHE_KEY,
            (int) config('cases.cache.departments_ttl', 60),
            fn (): array => Department::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (Department $department): array => $department->getRawOriginal())
                ->all(),
        );

        return Department::hydrate($rows);
    }

    /**
     * The departments a user may file a case into or filter by.
     *
     * @return Collection<int, Department>
     */
    public function availableTo(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return $this->active();
        }

        $accessible = $user->accessibleDepartmentIds();

        return $this->active()->filter(
            fn (Department $department): bool => in_array((int) $department->id, $accessible, true)
        )->values();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
