@props([
    'column' => [],
    'cards' => [],
    'handler' => 'moveCard',
    'group' => 'kanban',
    'animation' => 150,
    'width' => '18rem',
])

@php
    $cards = $cards instanceof \Illuminate\Support\Collection ? $cards : collect($cards);
    $columnId = $column['id'] ?? '';
    $color = $column['color'] ?? null;

    $dotClass = match($color) {
        'primary' => 'bg-kore-primary',
        'success' => 'bg-kore-success',
        'warning' => 'bg-kore-warning',
        'destructive' => 'bg-kore-destructive',
        'info' => 'bg-kore-info',
        'secondary' => 'bg-kore-secondary',
        default => 'bg-kore-muted-fg',
    };
@endphp

<div class="flex flex-col shrink-0 rounded-kore-lg bg-kore-muted/40 border border-kore-border" style="width: {{ $width }}">
    <header class="flex items-center justify-between px-3 py-2.5 border-b border-kore-border">
        <div class="flex items-center gap-2">
            @if($color)
                <span class="size-2 rounded-full {{ $dotClass }}"></span>
            @endif
            <span class="text-sm font-medium text-kore-fg">{{ $column['label'] ?? $columnId }}</span>
        </div>
        <span class="text-xs text-kore-muted-fg tabular-nums">{{ $cards->count() }}</span>
    </header>

    {{-- Sin `role`, un tablero es un montón de `div` anidados: un lector de
         pantalla no anuncia cuántas tarjetas hay en cada columna ni dónde
         empieza una y acaba otra. La lista lleva el nombre de la columna porque
         la cabecera de arriba es hermana, no ancestro, y no la nombra. --}}
    <div
        role="list"
        aria-label="{{ $column['label'] ?? $columnId }}"
        x-sort:group="{{ $group }}"
        x-sort="$wire.{{ $handler }}($item, $position, '{{ $columnId }}')"
        x-sort:config="{ animation: {{ (int) $animation }} }"
        class="flex flex-col gap-2 p-2 min-h-24 max-h-[70vh] overflow-y-auto"
    >
        @foreach($cards as $card)
            <x-kore::kanban.card :card="$card" />
        @endforeach
    </div>
</div>
