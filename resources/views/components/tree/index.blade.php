@props([
    'nodes' => [],
    'selectable' => false,
    'selectionMode' => 'single',
    'expandedKeys' => [],
    'selectedKeys' => [],
    'filter' => false,
    'filterPlaceholder' => null,
    'ariaLabel' => null,
])

@php
    $filterPlaceholder = $filterPlaceholder ?? config('kore-ui.ui.translations.tree_filter', 'Filtrar…');
    $ariaLabel = $ariaLabel ?? config('kore-ui.ui.translations.tree', 'Árbol');
@endphp

{{-- Los nodos viajan en un nodo JSON APARTE, fuera del `wire:ignore`.

     El árbol se pinta entero con `x-for` desde el cliente, y el morph de
     Livewire reemplazaba el `<template>` por el del servidor: a partir de ahí el
     `x-for` quedaba muerto —el estado decía nueve filas y el DOM se quedaba en
     siete, sin reaccionar ni tocando el estado a mano—. Con `wire:ignore` el
     morph no lo toca, y este `<script>` es la vía por la que el componente se
     entera de que el servidor cambió los datos. Mismo mecanismo que las
     opciones de `<x-kore::select>`. --}}
<script type="application/json" data-kore-tree-nodes>@json($nodes)</script>

<div {{ $attributes->class(['w-full']) }}
     wire:ignore
     x-data="KoreTree({
        {{-- Los nodos NO viajan aquí: van en el <script> de arriba y el
             componente los lee al iniciar. Con los dos sitios, un árbol de dos
             mil nodos mandaba el JSON por duplicado —medido: 81 kB de más—. --}}
        selectable: {{ $selectable ? 'true' : 'false' }},
        selectionMode: '{{ $selectionMode }}',
        expandedKeys: @js($expandedKeys),
        selectedKeys: @js($selectedKeys),
        filter: {{ $filter ? 'true' : 'false' }},
        labels: {
            expand: @js(config('kore-ui.ui.translations.tree_expand', 'Abrir')),
            collapse: @js(config('kore-ui.ui.translations.tree_collapse', 'Cerrar')),
        },
     })"
     role="tree"
     {{-- Un `role="tree"` sin nombre se anuncia como «árbol» y nada más. --}}
     aria-label="{{ $ariaLabel }}"
     x-on:keydown="onKeydown($event)">

    {{-- Filter --}}
    @if($filter)
        <div class="mb-3">
            <div class="relative">
                <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-kore-muted-fg" />
                <input type="text"
                       x-model.debounce.200ms="filterText"
                       {{-- El placeholder no es nombre accesible: desaparece en
                            cuanto se escribe algo, y era lo único que tenía
                            este campo. --}}
                       aria-label="{{ $filterPlaceholder }}"
                       placeholder="{{ $filterPlaceholder }}"
                       class="w-full pl-9 pr-3 py-2 text-sm rounded-kore-md border border-kore-border bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg focus:outline-none focus:ring-2 focus:ring-kore-ring" />
            </div>
        </div>
    @endif

    {{-- Flat-rendered nodes --}}
    <div class="space-y-0.5" role="group">
        <template x-for="item in flatNodes" :key="item.node.key">
            <div x-show="item.visible" class="select-none">
                {{-- El `aria-level` va AQUÍ, en el elemento con `role="treeitem"`,
                     y no en el envoltorio: en un `div` sin rol no significa nada
                     y el nivel no se anunciaba.

                     `tabindex` sigue el patrón de un tree: uno solo entra en el
                     tabulador y las flechas mueven el foco dentro. Antes todos
                     valían -1 y no había forma de llegar a un nodo con el
                     teclado —ni, por tanto, de seleccionar nada sin ratón—. --}}
                <div class="flex items-center gap-1 py-1 px-2 rounded-kore-sm cursor-pointer transition-colors hover:bg-kore-accent/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring"
                     :class="isSelected(item.node.key) && 'bg-kore-primary/10 text-kore-primary-text'"
                     :style="'padding-left: ' + (item.level * 20 + 8) + 'px'"
                     x-on:click="onNodeClick(item.node); focusKey = item.node.key"
                     role="treeitem"
                     :data-kore-tree-key="item.node.key"
                     :tabindex="esFoco(item.node.key) ? 0 : -1"
                     :aria-level="item.level + 1"
                     {{-- «3 de 7» en cada rama: ARIA los pide para que un lector
                          sepa por dónde va dentro del nivel, no solo en qué
                          nivel está. --}}
                     :aria-setsize="item.hermanos"
                     :aria-posinset="item.posicion"
                     :aria-expanded="item.hasChildren ? String(isExpanded(item.node.key)) : null"
                     :aria-selected="selectable ? String(isSelected(item.node.key)) : null">

                    {{-- Expand/collapse chevron --}}
                    <template x-if="item.hasChildren">
                        <button type="button"
                                x-on:click.stop="toggleExpand(item.node.key)"
                                class="inline-flex items-center justify-center size-5 rounded-kore-sm hover:bg-kore-accent transition-colors"
                                {{-- Con un nombre fijo, un lector anuncia lo mismo
                                     en cada rama y no dice cuál está abriendo.
                                     `tabindex=-1` porque quien recorre el árbol
                                     es el treeitem: el chevrón duplicaba paradas
                                     del tabulador sin aportar destino. --}}
                                tabindex="-1"
                                :aria-label="etiquetaDeChevron(item)">
                            <svg class="size-3.5 transition-transform duration-150"
                                 :class="isExpanded(item.node.key) && 'rotate-90'"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </template>

                    <template x-if="!item.hasChildren">
                        <span class="inline-block size-5"></span>
                    </template>

                    {{-- Checkbox --}}
                    @if($selectable && $selectionMode === 'checkbox')
                        <input type="checkbox"
                               :checked="isSelected(item.node.key)"
                               x-on:click.stop="toggleSelect(item.node.key)"
                               class="size-4 rounded border-kore-border text-kore-primary focus:ring-kore-ring" />
                    @endif

                    {{-- Label --}}
                    <span class="text-sm truncate" x-text="item.node.label"></span>
                </div>
            </div>
        </template>
    </div>
</div>
