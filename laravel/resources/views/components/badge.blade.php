@props([
    'variant' => 'default',
    'size' => 'md',
    'dot' => false,
])

@php
    $baseClasses = 'inline-flex items-center font-semibold uppercase tracking-wide whitespace-nowrap';

    $variants = [
        'default' => 'bg-dark-600 text-slate-300 border border-dark-400',
        'success' => 'bg-success-100 text-success-500 border border-success-200',
        'warning' => 'bg-warning-100 text-warning-500 border border-warning-200',
        'error' => 'bg-error-100 text-error-500 border border-error-200',
        'danger' => 'bg-error-100 text-error-500 border border-error-200',
        'info' => 'bg-info-100 text-info-500 border border-info-200',
        'cyan' => 'bg-cyan-100 text-cyan-500 border border-cyan-200',
        'orange' => 'bg-orange-100 text-orange-500 border border-orange-200',
        'gold' => 'bg-gold-100/20 text-gold-400 border border-gold-400/30',
        // Status-specific variants
        'delivered' => 'bg-success-100 text-success-500 border border-success-200',
        'returned' => 'bg-info-100 text-info-500 border border-info-200',
        'pending' => 'bg-dark-500/50 text-dark-100 border border-dark-400',
        'dispatched' => 'bg-orange-100 text-orange-500 border border-orange-200',
        'in_transit' => 'bg-cyan-100 text-cyan-500 border border-cyan-200',
        'in-transit' => 'bg-cyan-100 text-cyan-500 border border-cyan-200',
        'delivering' => 'bg-warning-100 text-warning-500 border border-warning-200',
        'hq_scheduling' => 'bg-info-100 text-info-500 border border-info-200',
        // Lead status variants
        'new' => 'bg-info-100 text-info-500 border border-info-200',
        'contacted' => 'bg-cyan-100 text-cyan-500 border border-cyan-200',
        'confirmed' => 'bg-success-100 text-success-500 border border-success-200',
        'submitted' => 'bg-gold-100/20 text-gold-400 border border-gold-400/30',
        'completed' => 'bg-success-100 text-success-500 border border-success-200',
        'lost' => 'bg-error-100 text-error-500 border border-error-200',
        'cancelled' => 'bg-dark-500/50 text-dark-100 border border-dark-400',
    ];

    $sizes = [
        'xs' => 'px-1.5 py-0.5 text-[9px] rounded',
        'sm' => 'px-2 py-0.5 text-[10px] rounded',
        'md' => 'px-2.5 py-1 text-[10px] rounded-md',
        'lg' => 'px-3 py-1.5 text-xs rounded-md',
    ];

    $classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['default']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full mr-1.5 @if($variant === 'success' || $variant === 'delivered' || $variant === 'confirmed' || $variant === 'completed') bg-success-500 @elseif($variant === 'error' || $variant === 'danger' || $variant === 'lost') bg-error-500 @elseif($variant === 'warning' || $variant === 'dispatched' || $variant === 'delivering') bg-warning-500 @elseif($variant === 'info' || $variant === 'new') bg-info-500 @else bg-current @endif"></span>
    @endif
    {{ $slot }}
</span>
