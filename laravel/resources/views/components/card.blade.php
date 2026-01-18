@props([
    'title' => null,
    'subtitle' => null,
    'padding' => true,
    'hover' => false,
])

@php
    $baseClasses = 'bg-dark-700 border border-dark-500 rounded-xl overflow-hidden';

    if ($hover) {
        $baseClasses .= ' transition-all duration-200 hover:border-gold-400 hover:-translate-y-0.5 hover:shadow-lg';
    }
@endphp

<div {{ $attributes->merge(['class' => $baseClasses]) }}>
    @if($title || isset($header))
        <div class="px-5 py-4 border-b border-dark-500 flex items-center justify-between">
            <div>
                @if($title)
                    <h2 class="text-lg font-semibold text-white">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="text-sm text-dark-100 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($header))
                <div class="flex items-center gap-2">
                    {{ $header }}
                </div>
            @endif
        </div>
    @endif

    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-5 py-4 border-t border-dark-500 bg-dark-800/50">
            {{ $footer }}
        </div>
    @endif
</div>
