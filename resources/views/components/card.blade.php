@props([
    'title' => null,
    'subtitle' => null,
    'image' => null,
    'imagePosition' => 'top',
    'href' => null,
    'target' => null,
    'collapsible' => false,
    'collapsed' => false,
    'loading' => false,
    'skeleton' => false,
    'bordered' => null,
    'shadow' => null,
    'padding' => null,
])

@php
    // El aspecto sale de la cascada: prop → `ui.card` → `ui.look` → el defecto
    // de aquí. Ver KoreUi\Core\Support\Look.
    $bordered = \KoreUi\Core\Support\Look::resolver('card', 'bordered', $bordered, true);
    $shadow = \KoreUi\Core\Support\Look::resolver('card', 'shadow', $shadow, true);
    $padding = \KoreUi\Core\Support\Look::resolver('card', 'padding', $padding, true);
    $tag = $href ? 'a' : 'div';

    // `loading` y `skeleton` no son lo mismo y conviven a propósito: el primero
    // echa un velo por encima de un contenido que ya está pintado, el segundo
    // dibuja la silueta de lo que todavía no ha llegado. Un entero elige cuántas
    // líneas tiene el cuerpo: `:skeleton="5"`.
    $siluetaActiva = $skeleton !== false && $skeleton !== null;
    $lineasSilueta = is_numeric($skeleton) ? (int) $skeleton : 3;
@endphp

@if($siluetaActiva)
    <x-kore::skeleton.card
        :lines="$lineasSilueta"
        :image="(bool) $image"
        :image-position="$imagePosition"
        :header="(bool) ($title || isset($header))"
        :footer="isset($footer)"
        :bordered="$bordered"
        :shadow="$shadow"
        :padding="$padding"
        {{ $attributes->except(['title', 'subtitle', 'image', 'imagePosition', 'href', 'target', 'collapsible', 'collapsed', 'loading', 'skeleton', 'bordered', 'shadow', 'padding']) }}
    />
@else

<{{ $tag }}
    {{ $attributes
        ->except(['title', 'subtitle', 'image', 'imagePosition', 'href', 'target', 'collapsible', 'collapsed', 'loading', 'skeleton', 'bordered', 'shadow', 'padding'])
        ->class([
            'relative rounded-kore-lg bg-kore-surface overflow-hidden',
            'border border-kore-border' => $bordered,
            'shadow-sm' => $shadow,
            'hover:shadow-md transition-shadow' => $href,
            'flex' => $image && $imagePosition === 'left',
        ])
    }}
    @if($href) href="{{ $href }}" @endif
    @if($target) target="{{ $target }}" @endif
    @if($collapsible) x-data="{ open: {{ $collapsed ? 'false' : 'true' }} }" @endif
>
    {{-- Image top/left --}}
    @if($image && in_array($imagePosition, ['top', 'left']))
        <div @class([
            'overflow-hidden',
            'w-48 shrink-0' => $imagePosition === 'left',
        ])>
            <img src="{{ $image }}" alt="" class="w-full h-full object-cover" />
        </div>
    @endif

    <div @class(['flex-1 min-w-0' => $image && $imagePosition === 'left'])>
        {{-- Header --}}
        @if($title || isset($header) || isset($action))
            @if($collapsible && $title && !isset($header))
                <button
                    type="button"
                    class="flex items-center justify-between gap-4 w-full text-left {{ $padding ? 'px-6 py-4' : 'px-4 py-3' }}"
                    x-on:click="open = !open"
                    :aria-expanded="open"
                >
                    <div class="min-w-0 flex-1">
                        <h3 class="flex items-center gap-2 text-base font-semibold text-kore-fg">
                            <x-lucide-chevron-right class="size-4 shrink-0 transition-transform duration-200" x-bind:class="open && 'rotate-90'" />
                            {{ $title }}
                        </h3>
                        @if($subtitle)
                            <p class="text-sm text-kore-muted-fg mt-1 ml-6">{{ $subtitle }}</p>
                        @endif
                    </div>
                    @if(isset($action))
                        <div class="shrink-0" @click.stop>{{ $action }}</div>
                    @endif
                </button>
            @else
                <div @class([
                    'flex items-start justify-between gap-4',
                    'px-6 pt-6' => $padding,
                    'px-4 pt-4' => !$padding,
                ])>
                    <div class="min-w-0 flex-1">
                        @if(isset($header))
                            {{ $header }}
                        @else
                            @if($title)
                                <h3 class="text-base font-semibold text-kore-fg">{{ $title }}</h3>
                            @endif
                            @if($subtitle)
                                <p class="text-sm text-kore-muted-fg mt-1">{{ $subtitle }}</p>
                            @endif
                        @endif
                    </div>

                    @if(isset($action))
                        <div class="shrink-0">{{ $action }}</div>
                    @endif
                </div>
            @endif
        @endif

        {{-- Body --}}
        @if($collapsible)
            <div
                class="grid transition-[grid-template-rows] duration-300"
                x-bind:class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
            >
                <div class="overflow-hidden">
                    <div @class(['px-6 py-4' => $padding, 'px-4 py-3' => !$padding])>
                        {{ $slot }}
                    </div>
                </div>
            </div>
        @else
            <div @class(['px-6 py-4' => $padding, 'px-4 py-3' => !$padding])>
                {{ $slot }}
            </div>
        @endif

        {{-- Footer --}}
        @if(isset($footer))
            <div @class([
                'border-t border-kore-border',
                'px-6 py-4' => $padding,
                'px-4 py-3' => !$padding,
            ])>
                {{ $footer }}
            </div>
        @endif
    </div>

    {{-- Image bottom --}}
    @if($image && $imagePosition === 'bottom')
        <div class="overflow-hidden">
            <img src="{{ $image }}" alt="" class="w-full h-auto object-cover" />
        </div>
    @endif

    {{-- Loading overlay --}}
    @if($loading)
        <div class="absolute inset-0 flex items-center justify-center bg-kore-surface/80 z-10">
            <x-kore::loading />
        </div>
    @endif
</{{ $tag }}>
@endif
