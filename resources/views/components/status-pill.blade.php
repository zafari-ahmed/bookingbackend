@props(['status'])

@php
    $status = $status instanceof \App\Enums\CaseStatus
        ? $status
        : \App\Enums\CaseStatus::from((string) $status);
@endphp

<span {{ $attributes->merge(['class' => 'pill '.$status->pillClasses()]) }}>
    {{ $status->label() }}
</span>
