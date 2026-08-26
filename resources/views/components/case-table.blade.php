@props(['cases'])

<div class="card overflow-hidden">
    {{-- Desktop and tablet: full table, horizontal scroll when it needs it. --}}
    <div class="hidden overflow-x-auto md:block">
        <table class="w-full min-w-[1180px] border-collapse text-sm">
            <thead>
                <tr class="border-b border-border">
                    <th class="whitespace-nowrap px-5 py-3.5 text-left">Case</th>
                    <th class="whitespace-nowrap px-4 py-3.5 text-left">Logged</th>
                    <th class="whitespace-nowrap px-4 py-3.5 text-left">Complainant</th>
                    <th class="px-4 py-3.5 text-left">Address</th>
                    <th class="px-4 py-3.5 text-left">Issue</th>
                    <th class="px-4 py-3.5 text-left">Department</th>
                    <th class="whitespace-nowrap px-4 py-3.5 text-left">Priority</th>
                    <th class="whitespace-nowrap px-5 py-3.5 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cases as $case)
                    <tr class="border-b border-border transition last:border-0 hover:bg-cream">
                        <td class="whitespace-nowrap px-5 py-3.5 font-mono text-xs text-text-muted">
                            <a href="{{ route('cases.show', $case) }}" class="font-semibold text-navy hover:underline">
                                {{ $case->case_number }}
                            </a>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3.5 text-text-secondary">
                            {{ $case->created_at->format('d M Y') }}
                            <span class="meta block">{{ $case->created_at->format('g:i A') }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3.5">
                            <span class="font-semibold text-text-primary">{{ $case->complainant_name }}</span>
                            <span class="meta block font-mono">{{ $case->complainant_phone }}</span>
                        </td>
                        <td class="max-w-[190px] px-4 py-3.5 leading-snug text-text-secondary">
                            {{ Str::limit($case->complainant_address, 60) }}
                        </td>
                        <td class="max-w-[260px] px-4 py-3.5 leading-snug text-text-primary">
                            {{ Str::limit($case->issue_summary, 90) }}
                        </td>
                        <td class="max-w-[160px] px-4 py-3.5 leading-snug text-text-secondary">
                            {{ $case->department->name }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3.5">
                            <x-priority-tag :priority="$case->priority" />
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <x-status-pill :status="$case->status" />
                            @if ($case->isOverdue())
                                <span class="meta mt-1 block text-overdue-text">{{ $case->daysOpen() }} days — overdue</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-16 text-center text-sm text-text-muted">
                            No cases match the current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile: one card per case (design-system.md §7). --}}
    <div class="divide-y divide-border md:hidden">
        @forelse ($cases as $case)
            <a href="{{ route('cases.show', $case) }}" class="block px-4 py-4 transition active:bg-cream">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-xs text-text-muted">{{ $case->case_number }}</p>
                        <p class="mt-1 truncate font-semibold text-text-primary">{{ $case->complainant_name }}</p>
                    </div>
                    <x-status-pill :status="$case->status" />
                </div>

                <p class="mt-2 text-sm leading-relaxed text-text-secondary">
                    {{ Str::limit($case->issue_summary, 110) }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                    <x-priority-tag :priority="$case->priority" />
                    <span class="meta">{{ $case->department->name }}</span>
                    <span class="meta">{{ $case->created_at->format('d M Y') }}</span>
                </div>
            </a>
        @empty
            <p class="px-4 py-14 text-center text-sm text-text-muted">No cases match the current filters.</p>
        @endforelse
    </div>

    @if ($cases->hasPages())
        <div class="border-t border-border px-5 py-3.5">
            {{ $cases->links() }}
        </div>
    @endif
</div>
