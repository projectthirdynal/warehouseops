@props([
    'label' => null,
    'name' => '',
    'id' => null,
    'value' => null,
    'placeholder' => 'Select an option',
    'options' => [],
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?? $errors->first($name);
    $selectedValue = old($name, $value);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-error-500">*</span>
            @endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $inputId }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->except('class')->merge([
            'class' => 'w-full h-10 px-3 bg-dark-800 border rounded-lg text-sm text-white transition-colors appearance-none cursor-pointer ' .
                ($hasError ? 'border-error-500 focus:border-error-500 focus:ring-error-500/20' : 'border-dark-400 focus:border-gold-400 focus:ring-gold-400/15') .
                ($disabled ? ' opacity-50 cursor-not-allowed' : '')
        ]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @if($selectedValue == $optionValue) selected @endif>
                    {{ $optionLabel }}
                </option>
            @endforeach
        @endif
    </select>

    @if($hasError)
        <p class="mt-1 text-xs text-error-500">{{ $errorMessage }}</p>
    @elseif($hint)
        <p class="mt-1 text-xs text-dark-100">{{ $hint }}</p>
    @endif
</div>
