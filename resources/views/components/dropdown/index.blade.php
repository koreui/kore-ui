@props([
    'position' => null,
    'width' => null,
    'maxHeight' => null,
    'persistent' => false,
])

@php
    $position = $position ?? config('kore-ui.ui.dropdown.position', 'bottom-start');
    $width = $width ?? config('kore-ui.ui.dropdown.width', 'auto');
    $maxHeightClass = $maxHeight ? '' : 'max-h-72';
@endphp

<div
    {{ $attributes
        ->except(['position', 'width', 'maxHeight', 'persistent'])
        ->class('relative inline-flex')
    }}
    x-data="KoreDropdown({
        position: '{{ $position }}',
        width: '{{ $width }}',
        persistent: {{ $persistent ? 'true' : 'false' }},
    })"
    x-on:keydown="onKeydown($event)"
>
    {{-- Trigger --}}
    <div x-ref="trigger" x-on:click="toggle()" class="inline-flex">
        {{ $trigger }}
    </div>

    {{-- Panel --}}
    <template x-teleport="body">
        <div
            data-kore-teleport
            x-ref="dropdown"
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-cloak
            role="menu"
            aria-orientation="vertical"
            class="z-50 rounded-kore-md border border-kore-border bg-kore-surface shadow-lg py-1 overflow-y-auto {{ $maxHeightClass }}"
            @if($maxHeight) style="max-height: {{ $maxHeight }}" @endif
        >
            {{ $slot }}
        </div>
    </template>
</div>
