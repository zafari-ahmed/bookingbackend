<x-layouts::app title="Dashboard">
    <x-slot:actions>
        @can('create', App\Models\CaseModel::class)
            <a href="{{ route('cases.create') }}" class="btn-primary">
                <x-icon name="plus" class="size-4" />
                <span class="hidden sm:inline">New Case</span>
            </a>
        @endcan
    </x-slot:actions>

    <div class="grid gap-6">
        <section aria-label="Case summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                label="Total Cases"
                :value="$stats['total']"
                tone="total"
                :href="route('cases.index')"
            />
            <x-stat-card
                label="Pending"
                :value="$stats['pending']"
                tone="pending"
                :href="route('cases.index', ['status' => App\Enums\CaseStatus::Pending->value])"
            />
            <x-stat-card
                label="Resolved"
                :value="$stats['resolved']"
                tone="resolved"
                :href="route('cases.index', ['status' => App\Enums\CaseStatus::Resolved->value])"
            />
            <x-stat-card
                label="Overdue"
                :value="$stats['overdue']"
                tone="overdue"
                :href="route('cases.index', ['overdue' => 1])"
            />
        </section>

        <section aria-label="Case register" class="grid gap-4">
            <x-case-filters
                :action="route('dashboard')"
                :filters="$filters"
                :departments="$departments"
                :statuses="$statuses"
                :priorities="$priorities"
            />

            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold tracking-tight text-navy">Recent Cases</h2>
                <p class="meta">
                    Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ number_format($cases->total()) }}
                </p>
            </div>

            <x-case-table :cases="$cases" />
        </section>
    </div>
</x-layouts::app>
