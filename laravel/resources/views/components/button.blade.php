@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium transition-all duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none';

    $variants = [
        'primary' => 'bg-gradient-to-br from-info-500 to-info-600 text-white shadow-md shadow-info-500/25 hover:from-info-600 hover:to-info-700 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-info-500/35',
        'secondary' => 'bg-dark-700 text-slate-200 border border-dark-400 hover:bg-dark-600 hover:border-info-500',
        'ghost' => 'bg-transparent text-slate-300 hover:bg-dark-700 hover:text-white',
        'danger' => 'bg-gradient-to-br from-error-500 to-error-600 text-white shadow-md shadow-error-500/25 hover:from-error-600 hover:to-error-700 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-error-500/35',
        'danger-outline' => 'bg-transparent text-error-500 border border-error-500 hover:bg-error-50',
        'success' => 'bg-gradient-to-br from-success-500 to-success-600 text-white shadow-md shadow-success-500/25 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-success-500/35',
        'warning' => 'bg-gradient-to-br from-warning-500 to-warning-600 text-black hover:-translate-y-0.5',
        'gold' => 'bg-gradient-to-br from-gold-400 to-gold-500 text-black shadow-md shadow-gold-500/25 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-gold-500/35',
        'info' => 'bg-gradient-to-br from-cyan-500 to-cyan-600 text-black font-semibold hover:-translate-y-0.5',
        'info-outline' => 'bg-transparent text-cyan-500 border border-cyan-500 hover:bg-cyan-50',
    ];

    $sizes = [
        'xs' => 'px-2.5 py-1 text-xs rounded-md',
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2.5 text-sm rounded-lg',
        'lg' => 'px-6 py-3.5 text-sm rounded-lg',
        'xl' => 'px-8 py-4 text-base rounded-xl',
        'icon' => 'p-2 rounded-lg w-9 h-9',
        'icon-sm' => 'p-1.5 rounded-md w-7 h-7',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($disabled) aria-disabled="true" tabindex="-1" @endif
    >
        @if($icon)
            <i class="{{ $icon }} @if($size === 'icon' || $size === 'icon-sm') text-sm @else text-xs @endif"></i>
        @endif
        {{ $slot }}
        @if($iconRight)
            <i class="{{ $iconRight }} text-xs"></i>
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($disabled) disabled @endif
    >
        @if($icon)
            <i class="{{ $icon }} @if($size === 'icon' || $size === 'icon-sm') text-sm @else text-xs @endif"></i>
        @endif
        {{ $slot }}
        @if($iconRight)
            <i class="{{ $iconRight }} text-xs"></i>
        @endif
    </button>
@endif
