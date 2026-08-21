@props([
    'items' => null,
    'separator' => null,
    'separatorClass' => null,
    'size' => null,
    'maxItems' => null,
    'jsonLd' => null,
])

@php
    // Resolve defaults from config
    $separator = $separator ?? config('kore-ui.breadcrumbs.separator', 'icon:chevron-right');
    $size = $size ?? config('kore-ui.breadcrumbs.size', 'md');
    $jsonLd = $jsonLd ?? config('kore-ui.breadcrumbs.json_ld', false);

    // Resolve items: manual props or registry
    if ($items === null) {
        try {
            $items = app(\KoreUi\Breadcrumbs\BreadcrumbManager::class)->generate();
        } catch (\Throwable) {
            $items = collect();
        }
    } elseif (is_array($items)) {
        $items = collect($items)->map(function ($item) {
            $obj = (object) $item;
            $obj->icon = $obj->icon ?? null;
            $obj->tooltip = $obj->tooltip ?? null;
            $obj->url = $obj->url ?? null;
            $obj->current = $obj->current ?? false;
            return $obj;
        });
    }

    if ($items instanceof \Illuminate\Support\Collection && $items->isEmpty()) {
        return;
    }

    if (is_countable($items) && count($items) === 0) {
        return;
    }

    if (! $items instanceof \Illuminate\Support\Collection) {
        $items = collect($items);
    }

    // Mark last item as current
    $items->last()->current = true;

    // Calculate collapsible
    $showCollapsible = $maxItems && $items->count() > $maxItems;
    $visibleStart = $showCollapsible ? $items->take(1) : collect();
    $hidden = $showCollapsible ? $items->slice(1, $items->count() - $maxItems + 1) : collect();
    $visibleEnd = $showCollapsible ? $items->slice(-($maxItems - 2)) : $items;

    // Size maps
    $sizeClasses = [
        'xs' => ['gap' => 'gap-0.5', 'text' => 'text-xs', 'icon' => 'size-3', 'sep' => 'size-3'],
        'sm' => ['gap' => 'gap-1', 'text' => 'text-sm', 'icon' => 'size-3.5', 'sep' => 'size-3.5'],
        'md' => ['gap' => 'gap-1.5', 'text' => 'text-sm', 'icon' => 'size-4', 'sep' => 'size-4'],
        'lg' => ['gap' => 'gap-2', 'text' => 'text-base', 'icon' => 'size-5', 'sep' => 'size-5'],
    ];
    $s = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Separator type
    $isSeparatorIcon = str_starts_with($separator, 'icon:');
    $separatorIcon = $isSeparatorIcon ? str_replace('icon:', '', $separator) : null;
@endphp

<nav {{ $attributes->class(['flex items-center']) }}
     aria-label="{{ config('kore-ui.ui.translations.breadcrumb', 'Ruta de navegación') }}">
    @isset($left)
        {{ $left }}
    @endisset

    {{-- El `x-data` vive en el <ol> y no en un <li> envolvente.

         Antes, el bloque colapsable metía los <li> del separador y de los items
         ocultos DENTRO de otro <li>, y eso es HTML inválido: el parser del
         navegador cierra el <li> exterior al encontrarse el interior, así que el
         botón y los `<template>` acababan como hermanos FUERA del elemento que
         llevaba el `x-data`. Resultado: `expanded is not defined` en cada
         expresión, y el desplegable de breadcrumbs sin funcionar. No daba error
         de compilación —el HTML que emite Blade es el que se escribió— y solo
         se ve mirando el DOM ya parseado. --}}
    <ol x-data="{ expanded: false }" class="flex items-center flex-wrap {{ $s['gap'] }}">
        @if ($showCollapsible)
            {{-- First item --}}
            @include('kore::components.breadcrumbs._item', ['item' => $visibleStart->first()])

            {{-- Separador + puntos suspensivos, como hermanos del <ol> --}}
            @include('kore::components.breadcrumbs._separator')

            <li x-show="!expanded" class="flex items-center {{ $s['gap'] }}">
                <button
                    type="button"
                    x-on:click="expanded = true"
                    class="flex items-center text-kore-muted-fg hover:text-kore-fg hover:bg-kore-muted rounded px-1 transition-colors"
                    aria-label="{{ config('kore-ui.ui.translations.breadcrumb_expand', 'Mostrar los pasos ocultos') }}"
                >
                    <x-lucide-ellipsis class="{{ $s['icon'] }}" />
                </button>
            </li>

            {{-- Items ocultos: `x-show` en cada <li>, sin envolturas ni
                 `<template>`, que aquí obligarían a anidar otra vez. --}}
            @foreach ($hidden as $hiddenItem)
                @include('kore::components.breadcrumbs._separator', ['extra' => 'x-show="expanded" x-cloak'])
                @include('kore::components.breadcrumbs._item', ['item' => $hiddenItem, 'extra' => 'x-show="expanded" x-cloak'])
            @endforeach

            {{-- Last visible items --}}
            @foreach ($visibleEnd as $item)
                @include('kore::components.breadcrumbs._separator')
                @include('kore::components.breadcrumbs._item', ['item' => $item])
            @endforeach
        @else
            {{-- Normal render --}}
            @foreach ($items as $index => $item)
                @if ($index > 0)
                    @include('kore::components.breadcrumbs._separator')
                @endif
                @include('kore::components.breadcrumbs._item', ['item' => $item])
            @endforeach
        @endif
    </ol>

    @isset($right)
        {{ $right }}
    @endisset
</nav>

@if ($jsonLd)
@php
    $jsonLdData = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items->values()->map(fn ($item, $i) => array_filter([
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item->label,
            'item' => $item->current ? null : $item->url,
        ]))->all(),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLdData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}</script>
@endif
