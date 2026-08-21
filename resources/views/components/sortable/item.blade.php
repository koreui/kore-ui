@props([
    'id' => null,
])

@aware([
    'mode' => null,
    'handle' => null,
])

@php
    $mode = $mode ?? config('kore-ui.ui.sortable.mode', 'server');
    $handle = $handle ?? config('kore-ui.ui.sortable.handle', false);

    $isServer = $mode === 'server';
    $p = $isServer ? 'wire:sort' : 'x-sort';

    // Para persistir el orden en el servidor hace falta un id de verdad (el del
    // modelo). Lo de abajo es solo el respaldo para reordenar en cliente, pero
    // con `uniqid()` ni siquiera eso funcionaba bien: el id va en el `wire:key`,
    // y un `wire:key` distinto en cada render obliga a Livewire a reemplazar
    // TODOS los items en cada ida y vuelta, en vez de actualizarlos. Medido: las
    // claves cambiaban enteras en cada morph. Ver IdContext.
    $id = $id ?? \KoreUi\Core\Support\IdContext::secuencia('sortable');
@endphp

<div
    {{-- En modo cliente esto es `x-sort:item`, y Alpine lo evalúa como una
         EXPRESIÓN de JavaScript: un id de texto sin comillas se lee como una
         resta de variables y suelta un ReferenceError en cada item. En modo
         servidor es `wire:sort:item`, que sí es un valor plano y no se toca. --}}
    @if($isServer)
        {{ $p }}:item="{{ $id }}"
    @else
        {{ $p }}:item="{{ Js::from($id) }}"
    @endif
    wire:key="kore-sortable-{{ $id }}"
    {{ $attributes->except(['id'])->class([
        'flex items-center gap-2 rounded-kore-md border border-kore-border bg-kore-surface px-3 py-2',
        'cursor-grab' => ! $handle,
    ]) }}
>
    @if($handle)
        <button
            type="button"
            {{ $p }}:handle
            class="shrink-0 cursor-grab text-kore-muted-fg hover:text-kore-fg transition-colors"
            aria-label="{{ config('kore-ui.ui.translations.reorder', 'Arrastrar para reordenar') }}"
        >
            <x-lucide-grip-vertical class="size-4" />
        </button>
    @endif

    {{ $slot }}
</div>
