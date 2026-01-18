@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 ' .
            ($active
                ? 'bg-gold-500/10 text-gold-400 border border-gold-500/20'
                : 'text-slate-400 hover:bg-dark-700 hover:text-white')
    ]) }}
    @if($active) aria-current="page" @endif
>
    @if($icon)
        <i class="{{ $icon }} w-5 {{ $active ? 'text-gold-400' : 'text-dark-100 group-hover:text-white' }} transition-colors"></i>
    @endif
    {{ $slot }}
</a>
