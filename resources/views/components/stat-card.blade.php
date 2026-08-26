@props([
    'label',
    'value',
    'tone' => 'total',
    'href' => null,
])

@php
    // Stat card background/text pairs, design-system.md section 1.
    $tones = [
        'total' => 'bg-total-bg text-total-text border-border',
        'pending' => 'bg-pending-bg text-pending-text border-pending-text/20',
        'resolved' => 'bg-resolved-bg text-resolved-text border-resolved-text/20',
        'overdue' => 'bg-overdue-bg text-overdue-text border-overdue-text/20',
    ];

    $classes = 'block rounded-card border px-5 py-5 transition '.($tones[$tone] ?? $tones['total'])
        .($href ? ' hover:-translate-y-px hover:shadow-[0_6px_18px_rgba(19,42,69,0.06)]' : '');
@endphp

<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    <p class="text-xs font-semibold uppercase tracking-[0.06em] {{ $tone === 'total' ? 'text-text-muted' : '' }}">
        {{ $label }}
    </p>
    {{-- Section 2 — stat numbers: bold, large --}}
    <p class="mt-2 text-4xl font-extrabold tracking-tight {{ $tone === 'total' ? 'text-navy' : '' }}">
        {{ number_format($value) }}
    </p>
</{{ $href ? 'a' : 'div' }}>
