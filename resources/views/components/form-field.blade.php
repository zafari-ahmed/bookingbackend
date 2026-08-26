@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'hint' => null,
    'rows' => 3,
    'mono' => false,
    'options' => null,
    'placeholderOption' => null,
    'readonly' => false,
    'disabled' => false,
])

@php
    $id = $name.'-field';
    $value = old($name, $value);
    $locked = $readonly || $disabled;
    $inputClasses = 'field'
        .($mono ? ' font-mono text-[13.5px]' : '')
        .($errors->has($name) ? ' border-escalated-text' : '')
        .($locked ? ' cursor-not-allowed bg-cream text-text-secondary' : '');
@endphp

<div {{ $attributes->merge(['class' => 'grid gap-1.5']) }}>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if ($required && ! $locked)
            <span class="text-escalated-text" aria-hidden="true">*</span>
            <span class="sr-only">(required)</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" @unless ($locked) name="{{ $name }}" @endunless rows="{{ $rows }}"
                  @if ($required && ! $locked) required @endif
                  @if ($readonly) readonly @endif
                  @if ($disabled) disabled @endif
                  placeholder="{{ $placeholder }}"
                  class="{{ $inputClasses }} resize-y leading-relaxed">{{ $value }}</textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" @unless ($locked) name="{{ $name }}" @endunless
                @if ($required && ! $locked) required @endif
                @if ($disabled) disabled @endif
                class="{{ $inputClasses }}">
            @if ($placeholderOption)
                <option value="">{{ $placeholderOption }}</option>
            @endif
            @foreach ($options ?? [] as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" @unless ($locked) name="{{ $name }}" @endunless value="{{ $value }}"
               @if ($required && ! $locked) required @endif
               @if ($readonly) readonly @endif
               @if ($disabled) disabled @endif
               @if ($type === 'email') autocomplete="username" @endif
               placeholder="{{ $placeholder }}"
               class="{{ $inputClasses }}">
    @endif

    @error($name)
        <p class="field-error">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="meta">{{ $hint }}</p>
        @endif
    @enderror
</div>
