@props([
    'value' => 0,
    'label' => '',
    'variant' => 'default',
    'icon' => null,
    'suffix' => '',
    'trend' => null,
    'trendValue' => null,
    'href' => null,
])

@php
    $baseClasses = 'relative overflow-hidden bg-gradient-to-br from-dark-700 to-dark-700/80 border border-dark-500 rounded-xl p-5 min-h-24 transition-all duration-200 hover:border-info-500 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-glow-blue';

    $valueColors = [
        'default' => 'text-cyan-500',
        'success' => 'text-success-500',
        'warning' => 'text-warning-500',
        'danger' => 'text-error-500',
        'error' => 'text-error-500',
        'info' => 'text-info-500',
        'cyan' => 'text-cyan-500',
        'orange' => 'text-orange-500',
        'gold' => 'text-gold-400',
        'pending' => 'text-dark-100',
        'dispatched' => 'text-orange-500',
        'delivered' => 'text-success-500',
        'returned' => 'text-info-500',
    ];

    $valueColor = $valueColors[$variant] ?? $valueColors['default'];
@endphp

@if($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses . ' block cursor-pointer']) }}>
@else
<article {{ $attributes->merge(['class' => $baseClasses]) }}>
@endif
    {{-- Top gradient line --}}
    <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>

    <div class="flex flex-col justify-center h-full">
        @if($icon)
            <div class="absolute top-4 right-4 text-dark-400 text-2xl">
                <i class="{{ $icon }}"></i>
            </div>
        @endif

        <div class="flex items-baseline gap-1">
            <h3 class="text-3xl font-bold {{ $valueColor }} tracking-tight leading-none">
                {{ is_numeric($value) ? number_format($value) : $value }}
            </h3>
            @if($suffix)
                <span class="text-base font-bold {{ $valueColor }} opacity-70">{{ $suffix }}</span>
            @endif
        </div>

        <p class="text-xs font-medium text-dark-100 uppercase tracking-widest mt-1">{{ $label }}</p>

        @if($trend !== null && $trendValue !== null)
            <div class="flex items-center gap-1 mt-2 text-xs">
                @if($trend === 'up')
                    <i class="fas fa-arrow-up text-success-500"></i>
                    <span class="text-success-500">{{ $trendValue }}</span>
                @elseif($trend === 'down')
                    <i class="fas fa-arrow-down text-error-500"></i>
                    <span class="text-error-500">{{ $trendValue }}</span>
                @else
                    <i class="fas fa-minus text-dark-100"></i>
                    <span class="text-dark-100">{{ $trendValue }}</span>
                @endif
            </div>
        @endif
    </div>

    {{ $slot }}
@if($href)
</a>
@else
</article>
@endif
