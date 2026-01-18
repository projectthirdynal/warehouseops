@props([
    'title' => null,
    'subtitle' => null,
    'striped' => false,
    'hoverable' => true,
])

<div {{ $attributes->merge(['class' => 'bg-dark-700 border border-dark-500 rounded-xl overflow-hidden shadow-md']) }}>
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

    <div class="overflow-x-auto">
        <table class="w-full">
            @if(isset($head))
                <thead class="bg-dark-900">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
            @endif
            <tbody @class(['divide-y divide-dark-600' => !$striped])>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if(isset($footer))
        <div class="px-5 py-4 border-t border-dark-600 bg-dark-800/50">
            {{ $footer }}
        </div>
    @endif
</div>
