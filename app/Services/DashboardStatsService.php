<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CaseStatus;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Stat-card counts for the top of the Dashboard.
 *
 * Cached per user (the numbers differ by department access) with a short TTL —
 * a 30-second-old case count is fine, four aggregate queries on every page load
 * are not.
 */
class DashboardStatsService
{
    /**
     * @return array{total: int, pending: int, resolved: int, overdue: int}
     */
    public function forUser(User $user): array
    {
        return Cache::remember(
            $this->cacheKey($user),
            (int) config('cases.cache.stats_ttl', 30),
            fn (): array => $this->compute(),
        );
    }

    public function forget(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    /**
     * A single grouped query rather than four COUNTs. The global scope is
     * applied by Eloquent, so these totals are already department-filtered.
     *
     * @return array{total: int, pending: int, resolved: int, overdue: int}
     */
    private function compute(): array
    {
        $byStatus = CaseModel::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $open = collect(CaseStatus::openStatuses())
            ->sum(fn (CaseStatus $status): int => (int) $byStatus->get($status->value, 0));

        return [
            'total' => (int) $byStatus->sum(),
            'pending' => $open,
            'resolved' => (int) $byStatus->get(CaseStatus::Resolved->value, 0),
            'overdue' => CaseModel::query()->overdue()->count(),
        ];
    }

    private function cacheKey(User $user): string
    {
        return 'dashboard:stats:'.$user->getKey();
    }
}
