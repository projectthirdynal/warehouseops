@props([
    'sortable' => false,
    'sortKey' => null,
    'currentSort' => null,
    'currentDirection' => 'asc',
])

@php
    $isSorted = $sortKey && $currentSort === $sortKey;
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-dark-100 border-b border-dark-500']) }}>
    @if($sortable && $sortKey)
        <button
            type="button"
            class="flex items-center gap-1 hover:text-white transition-colors group"
            onclick="window.location.href = '{{ request()->fullUrlWithQuery(['sort' => $sortKey, 'direction' => $isSorted && $currentDirection === 'asc' ? 'desc' : 'asc']) }}'"
        >
            {{ $slot }}
            <span class="@if(!$isSorted) opacity-0 group-hover:opacity-50 @endif transition-opacity">
                @if($isSorted && $currentDirection === 'desc')
                    <i class="fas fa-sort-down text-gold-400"></i>
                @elseif($isSorted && $currentDirection === 'asc')
                    <i class="fas fa-sort-up text-gold-400"></i>
                @else
                    <i class="fas fa-sort"></i>
                @endif
            </span>
        </button>
    @else
        {{ $slot }}
    @endif
</th>
