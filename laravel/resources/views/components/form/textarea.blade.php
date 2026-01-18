@props([
    'label' => null,
    'name' => '',
    'id' => null,
    'value' => null,
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'rows' => 4,
    'error' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? $name;
    $hasError = $error || $errors->has($name);
    $errorMessage = $error ?? $errors->first($name);
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

    <textarea
        name="{{ $name }}"
        id="{{ $inputId }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($readonly) readonly @endif
        {{ $attributes->except('class')->merge([
            'class' => 'w-full px-3 py-2 bg-dark-800 border rounded-lg text-sm text-white placeholder-dark-100 transition-colors resize-y min-h-[100px] ' .
                ($hasError ? 'border-error-500 focus:border-error-500 focus:ring-error-500/20' : 'border-dark-400 focus:border-gold-400 focus:ring-gold-400/15') .
                ($disabled ? ' opacity-50 cursor-not-allowed' : '')
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if($hasError)
        <p class="mt-1 text-xs text-error-500">{{ $errorMessage }}</p>
    @elseif($hint)
        <p class="mt-1 text-xs text-dark-100">{{ $hint }}</p>
    @endif
</div>
