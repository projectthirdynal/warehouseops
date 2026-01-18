@props([
    'type' => 'info',
    'dismissible' => false,
    'icon' => null,
])

@php
    $baseClasses = 'px-4 py-3 rounded-lg flex items-center gap-3 text-sm font-medium';

    $types = [
        'success' => 'bg-success-50 border border-success-200 text-success-500',
        'error' => 'bg-error-50 border border-error-200 text-error-500',
        'danger' => 'bg-error-50 border border-error-200 text-error-500',
        'warning' => 'bg-warning-50 border border-warning-200 text-warning-500',
        'info' => 'bg-info-50 border border-info-200 text-info-500',
    ];

    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'danger' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle',
    ];

    $typeClasses = $types[$type] ?? $types['info'];
    $defaultIcon = $icons[$type] ?? $icons['info'];
@endphp

<div
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $typeClasses]) }}
    @if($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
    role="alert"
>
    <i class="{{ $icon ?? $defaultIcon }}"></i>
    <div class="flex-1">{{ $slot }}</div>
    @if($dismissible)
        <button
            type="button"
            @click="show = false"
            class="ml-auto hover:opacity-70 transition-opacity"
            aria-label="Dismiss"
        >
            <i class="fas fa-times"></i>
        </button>
    @endif
</div>
