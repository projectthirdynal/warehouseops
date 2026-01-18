@props([
    'icon' => 'fas fa-inbox',
    'title' => 'No data available',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-4']) }}>
    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-dark-600 flex items-center justify-center">
        <i class="{{ $icon }} text-2xl text-dark-100"></i>
    </div>
    <h3 class="text-lg font-medium text-white mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-dark-100 mb-4">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
