@props([
    'number' => '',
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center bg-info-100 text-info-500 px-2.5 py-1 rounded text-xs font-semibold font-mono tracking-tight border border-info-200']) }}>
    {{ $number ?: $slot }}
</span>
