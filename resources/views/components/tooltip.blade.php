@props([
    'text' => null,
    'position' => null,
    'delay' => null,
])

@php
    $position = $position ?? config('kore-ui.ui.tooltip.position', 'top');
    $delay = $delay ?? config('kore-ui.ui.tooltip.delay', 200);
@endphp

<div
    {{ $attributes
        ->except(['text', 'position', 'delay'])
        ->class('inline-flex')
    }}
    x-data="KoreTooltip({ placement: '{{ $position }}', delay: {{ $delay }} })"
    x-on:mouseenter="open()"
    x-on:mouseleave="close()"
    x-on:focus.capture="open()"
    x-on:blur.capture="close()"
>
    <div x-ref="trigger" class="inline-flex">
        {{ $slot }}
    </div>

    <template x-teleport="body">
        <div
            data-kore-teleport
            x-ref="tooltip"
            x-show="show"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            role="tooltip"
            class="z-[70] pointer-events-none max-w-xs"
        >
            <div class="bg-kore-fg text-kore-bg rounded-kore-md text-xs px-2.5 py-1.5 font-medium">
                {{ $text }}
            </div>
        </div>
    </template>
</div>
