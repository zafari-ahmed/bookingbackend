<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActivityPeriod;
use App\Models\CaseActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = ActivityPeriod::fromQuery($request->query('period'));

        $logs = CaseActivityLog::query()
            ->where('user_id', $request->user()->getKey())
            ->with('case')
            ->forPeriod($period)
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) config('cases.per_page', 20))
            ->withQueryString();

        return view('activity.index', [
            'logs' => $logs,
            'period' => $period,
            'periods' => ActivityPeriod::tabs(),
        ]);
    }
}
