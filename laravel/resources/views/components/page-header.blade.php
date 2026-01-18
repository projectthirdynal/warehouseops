@props([
    'title' => '',
    'description' => null,
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-white flex items-center gap-3">
                @if($icon)
                    <span class="text-cyan-500">
                        <i class="{{ $icon }}"></i>
                    </span>
                @endif
                {{ $title }}
            </h2>
            @if($description)
                <p class="text-sm text-dark-100 mt-1">{{ $description }}</p>
            @endif
        </div>
        @if($slot->isNotEmpty())
            <div class="flex items-center gap-3">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
