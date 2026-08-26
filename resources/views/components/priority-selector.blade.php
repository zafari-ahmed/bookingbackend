@props(['priorities', 'selected' => null])

@php
    $selected = old('priority', $selected instanceof \App\Enums\CasePriority ? $selected->value : $selected)
        ?? \App\Enums\CasePriority::Normal->value;

    $hints = collect($priorities)
        ->mapWithKeys(fn ($priority): array => [$priority->value => $priority->hint()])
        ->all();
@endphp

{{-- Segmented control with a coloured active state (design-system.md §5). --}}
<div
    x-data="{ priority: @js($selected), hints: @js($hints) }"
    {{ $attributes->merge(['class' => 'grid gap-1.5']) }}
>
    <span class="field-label">
        Priority
        <span class="text-escalated-text" aria-hidden="true">*</span>
        <span class="sr-only">(required)</span>
    </span>

    <div class="flex min-h-11 w-full min-w-0 items-stretch gap-1 rounded-control border border-border bg-cream p-1" role="radiogroup" aria-label="Priority">
        @foreach ($priorities as $priority)
            <label class="min-w-0 flex-1 cursor-pointer">
                <input type="radio" name="priority" value="{{ $priority->value }}"
                       x-model="priority" class="sr-only peer">
                <span
                    class="flex h-full min-h-9 items-center justify-center rounded-[9px] px-1.5 text-center text-xs font-bold transition sm:px-3 sm:text-[13.5px]"
                    :class="priority === @js($priority->value)
                        ? @js($priority->pillClasses())
                        : 'text-text-muted hover:text-text-secondary'"
                >{{ $priority->label() }}</span>
            </label>
        @endforeach
    </div>

    <div>
        <p class="meta leading-snug" x-text="hints[priority]"></p>

        @error('priority')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>
</div>
