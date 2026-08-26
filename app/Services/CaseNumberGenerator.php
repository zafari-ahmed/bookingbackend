<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CaseModel;
use App\Models\Scopes\DepartmentScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Produces the human-facing case reference, e.g. CASE-2026-0001.
 *
 * The sequence restarts each calendar year, matching how the AC office numbers
 * its paper register.
 */
class CaseNumberGenerator
{
    public const PREFIX = 'CASE';

    public function next(?int $year = null): string
    {
        $year ??= (int) Carbon::now()->year;
        $prefix = sprintf('%s-%d-', self::PREFIX, $year);

        // Runs inside the caller's transaction; the row lock prevents two
        // simultaneous submissions from claiming the same number.
        $latest = CaseModel::query()
            ->withoutGlobalScope(DepartmentScope::class)
            ->withTrashed()
            ->where('case_number', 'like', $prefix.'%')
            ->when(
                DB::connection()->getDriverName() === 'mysql',
                fn ($query) => $query->lockForUpdate()
            )
            ->orderByDesc('case_number')
            ->value('case_number');

        $sequence = $latest === null
            ? 1
            : ((int) substr($latest, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
