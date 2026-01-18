@props([
    'highlight' => false,
])

<td {{ $attributes->merge(['class' => 'px-4 py-3 text-sm text-slate-200 ' . ($highlight ? 'font-semibold text-white' : '')]) }}>
    {{ $slot }}
</td>
