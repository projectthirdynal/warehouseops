@props([
    'action' => null,
    'method' => 'GET',
])

<div {{ $attributes->merge(['class' => 'bg-dark-700 border border-dark-500 rounded-xl p-4 mb-5']) }}>
    <form
        @if($action) action="{{ $action }}" @endif
        method="{{ $method }}"
        class="flex flex-wrap items-end gap-3"
    >
        @if($method !== 'GET')
            @csrf
        @endif
        {{ $slot }}
    </form>
</div>
