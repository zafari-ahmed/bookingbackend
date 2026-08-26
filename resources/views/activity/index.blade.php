<x-layouts::app title="My Activity">
    <div class="grid gap-4">
        <nav aria-label="Activity period" class="card overflow-x-auto px-2 py-2">
            <ul class="flex min-w-max items-center gap-1">
                @foreach ($periods as $item)
                    @php $isActive = $period === $item; @endphp
                    <li>
                        <a
                            href="{{ route('activity.index', $item === \App\Enums\ActivityPeriod::Recent ? [] : ['period' => $item->value]) }}"
                            @class([
                                'flex min-h-11 items-center rounded-control px-4 text-[13.5px] font-bold transition',
                                'bg-navy text-text-inverse' => $isActive,
                                'text-text-secondary hover:bg-cream' => ! $isActive,
                            ])
                            @if ($isActive) aria-current="page" @endif
                        >
                            {{ $item->label() }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="card overflow-hidden">
            <ul class="divide-y divide-border">
                @forelse ($logs as $log)
                    <li class="px-4 py-4 sm:px-5">
                        <div class="flex items-start gap-3.5">
                            <span class="mt-1.5 size-2.5 shrink-0 rounded-full {{ $log->action_type->dotClass() }}" aria-hidden="true"></span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="pill {{ $log->action_type->pillClasses() }}">
                                        {{ $log->action_type->label() }}
                                    </span>
                                    @if ($log->case)
                                        <a href="{{ route('cases.show', $log->case) }}"
                                           class="font-mono text-xs font-semibold text-referred-text hover:text-navy">
                                            {{ $log->case->case_number }}
                                        </a>
                                    @endif
                                </div>

                                <p class="mt-1.5 text-sm leading-relaxed text-text-primary">
                                    {{ $log->description }}
                                </p>

                                <p class="meta mt-1.5">
                                    {{ $log->created_at->format('d M Y, g:i A') }}
                                    <span aria-hidden="true">·</span>
                                    {{ $log->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-20 text-center">
                        <p class="text-sm font-bold text-text-secondary">No activity in this period.</p>
                        <p class="meta mx-auto mt-1.5 max-w-sm">
                            Cases you log, remarks you post and files you attach will appear here.
                        </p>
                    </li>
                @endforelse
            </ul>

            @if ($logs->hasPages())
                <div class="border-t border-border px-5 py-3.5">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
