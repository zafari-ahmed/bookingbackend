@props(['comments'])

{{-- One card per remark, department tag, timestamp, and a left border colour
     coded by department (design-system.md §5). --}}
<div class="grid gap-3">
    @forelse ($comments as $comment)
<article class="min-w-0 rounded-card border border-border border-l-[3px] bg-cream px-3 py-3.5 sm:px-4 {{ $comment->department?->accentClass() ?? 'border-l-teal' }}">
            <div class="flex flex-wrap items-start gap-x-2.5 gap-y-1">
                <span class="text-[13px] font-bold text-text-primary">
                    {{ $comment->user?->name ?? 'Removed officer' }}
                </span>

                @if ($comment->department)
                    <span class="pill max-w-full truncate {{ $comment->department->tagClasses() }}">{{ $comment->department->name }}</span>
                @endif

                <span class="meta w-full sm:ml-auto sm:w-auto">{{ $comment->created_at->format('d M Y, g:i A') }}</span>
            </div>

            <p class="mt-2 break-words text-sm leading-relaxed text-text-secondary">{{ $comment->comment }}</p>

            @if ($comment->forwardedToDepartment)
                <p class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-referred-text">
                    <x-icon name="forward" class="size-3.5" />
                    Forwarded to {{ $comment->forwardedToDepartment->name }}
                </p>
            @endif
        </article>
    @empty
        <p class="py-8 text-center text-sm text-text-muted">No remarks recorded on this case yet.</p>
    @endforelse
</div>
