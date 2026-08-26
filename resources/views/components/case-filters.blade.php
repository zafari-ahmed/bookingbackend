@props([
    'action',
    'filters',
    'departments',
    'statuses',
    'priorities',
])

{{-- Filters submit as GET query params so the register stays bookmarkable and
     the pagination links carry the state via withQueryString(). --}}
<form method="GET" action="{{ $action }}" class="card px-4 py-4 sm:px-5">
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,2fr)_repeat(3,minmax(0,1fr))_auto]">
        <div class="relative">
            <label for="filter-q" class="sr-only">Search cases</label>
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted">
                <x-icon name="search" class="size-4" />
            </span>
            <input id="filter-q" type="search" name="q" value="{{ $filters['search'] }}"
                   placeholder="Name, CNIC or case number"
                   class="field pl-10">
        </div>

        <div>
            <label for="filter-department" class="sr-only">Department</label>
            <select id="filter-department" name="department" class="field">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($filters['department'] === (int) $department->id)>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-status" class="sr-only">Status</label>
            <select id="filter-status" name="status" class="field">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="filter-priority" class="sr-only">Priority</label>
            <select id="filter-priority" name="priority" class="field">
                <option value="">Any priority</option>
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}" @selected($filters['priority'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="btn-primary w-full xl:w-auto">Filter</button>
            @if (array_filter($filters))
                <a href="{{ $action }}" class="btn-secondary w-full xl:w-auto">Clear</a>
            @endif
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-border pt-3">
        <label class="inline-flex items-center gap-2 text-xs font-semibold text-text-secondary">
            <input type="checkbox" name="high_only" value="1" @checked($filters['high_only'])
                   class="size-4 rounded border-border text-teal focus:ring-teal">
            High priority only
        </label>

        <label class="inline-flex items-center gap-2 text-xs font-semibold text-text-secondary">
            <input type="checkbox" name="overdue" value="1" @checked($filters['overdue'])
                   class="size-4 rounded border-border text-teal focus:ring-teal">
            Overdue (open more than {{ config('cases.overdue_after_days', 7) }} days)
        </label>
    </div>
</form>
