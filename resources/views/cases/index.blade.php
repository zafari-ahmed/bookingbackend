<x-layouts::app :title="$heading">
    <x-slot:actions>
        @can('create', App\Models\CaseModel::class)
            <a href="{{ route('cases.create') }}" class="btn-primary">
                <x-icon name="plus" class="size-4" />
                <span class="hidden sm:inline">New Case</span>
            </a>
        @endcan
    </x-slot:actions>

    <div class="grid gap-4">
        <x-case-filters
            :action="route('cases.index')"
            :filters="$filters"
            :departments="$departments"
            :statuses="$statuses"
            :priorities="$priorities"
        />

        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-bold tracking-tight text-navy">{{ $heading }}</h2>
            <p class="meta">
                Showing {{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }} of {{ number_format($cases->total()) }}
            </p>
        </div>

        <x-case-table :cases="$cases" />
    </div>
</x-layouts::app>
