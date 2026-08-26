@props(['logs'])

{{-- Vertical connecting line, coloured dot per event, muted timestamp above
     the action text (design-system.md §5). --}}
<ol class="grid">
    @forelse ($logs as $log)
        <li class="grid grid-cols-[22px_minmax(0,1fr)] gap-3">
            <div class="flex flex-col items-center">
                <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $log->action_type->dotClass() }}"></span>
                @unless ($loop->last)
                    <span class="w-px flex-1 bg-border"></span>
                @endunless
            </div>
            <div class="{{ $loop->last ? '' : 'pb-5' }}">
                <p class="meta">{{ $log->created_at->format('d M Y, g:i A') }}</p>
                <p class="mt-0.5 break-words text-[13px] font-semibold leading-snug text-text-primary">{{ $log->description }}</p>
                <p class="mt-0.5 text-xs leading-snug text-text-secondary">{{ $log->actorLabel() }}</p>
            </div>
        </li>
    @empty
        <li class="py-6 text-center text-sm text-text-muted">No activity recorded yet.</li>
    @endforelse
</ol>
