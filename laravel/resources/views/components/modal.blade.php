@props([
    'name' => 'modal',
    'title' => null,
    'maxWidth' => 'lg',
    'closeable' => true,
])

@php
    $maxWidthClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        'full' => 'max-w-full mx-4',
    ];

    $maxWidthClass = $maxWidthClasses[$maxWidth] ?? $maxWidthClasses['lg'];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title-{{ $name }}"
    aria-modal="true"
    role="dialog"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-dark-950/80 backdrop-blur-sm"
        @if($closeable) @click="open = false" @endif
    ></div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full {{ $maxWidthClass }} bg-dark-700 border border-dark-500 rounded-xl shadow-xl overflow-hidden"
                @click.stop
            >
                {{-- Header --}}
                @if($title || $closeable)
                    <div class="px-5 py-4 border-b border-dark-500 flex items-center justify-between">
                        @if($title)
                            <h3 id="modal-title-{{ $name }}" class="text-lg font-semibold text-white">{{ $title }}</h3>
                        @else
                            <div></div>
                        @endif
                        @if($closeable)
                            <button
                                type="button"
                                @click="open = false"
                                class="text-dark-100 hover:text-white transition-colors p-1 rounded-lg hover:bg-dark-600"
                                aria-label="Close modal"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Content --}}
                <div class="p-5">
                    {{ $slot }}
                </div>

                {{-- Footer --}}
                @if(isset($footer))
                    <div class="px-5 py-4 border-t border-dark-500 bg-dark-800/50 flex items-center justify-end gap-3">
                        {{ $footer }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
