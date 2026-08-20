@props([
    'card' => [],
])

<div
    wire:key="kore-card-{{ $card['id'] ?? '' }}"
    {{-- `x-sort:item` es una EXPRESIÓN de JavaScript para Alpine: un id de texto
         sin comillas se lee como una resta de variables y suelta un
         `ReferenceError` por cada tarjeta —medido: con `id => 'tarea-a'`, «tarea
         is not defined»—. Con ids numéricos funcionaba de casualidad. Es el
         mismo fallo que `<x-kore::sortable>` ya tenía corregido en modo cliente
         (§A.7 del informe de formulario); el tablero se quedó fuera. --}}
    x-sort:item="{{ Js::from((string) ($card['id'] ?? '')) }}"
    role="listitem"
    {{ $attributes->except(['card'])->class(['rounded-kore-md border border-kore-border bg-kore-surface p-3 shadow-sm cursor-grab transition-shadow hover:shadow-md']) }}
>
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        <p class="text-sm font-medium text-kore-fg">{{ $card['title'] ?? '' }}</p>

        @isset($card['description'])
            <p class="mt-1 text-xs text-kore-muted-fg">{{ $card['description'] }}</p>
        @endisset

        @isset($card['badge'])
            <div class="mt-2">
                <x-kore::badge :label="$card['badge']" :color="$card['badgeColor'] ?? 'muted'" size="sm" />
            </div>
        @endisset
    @endif
</div>
