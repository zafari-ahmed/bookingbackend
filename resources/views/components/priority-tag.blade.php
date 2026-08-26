@props(['priority', 'variant' => 'dot'])

@php
    $priority = $priority instanceof \App\Enums\CasePriority
        ? $priority
        : \App\Enums\CasePriority::from((string) $priority);
@endphp

@if ($variant === 'pill')
    <span {{ $attributes->merge(['class' => 'pill '.$priority->pillClasses()]) }}>
        {{ $priority->label() }} priority
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-xs font-semibold '.$priority->textClass()]) }}>
        <span class="size-[7px] rounded-full {{ $priority->dotClass() }}"></span>
        {{ $priority->label() }}
    </span>
@endif
