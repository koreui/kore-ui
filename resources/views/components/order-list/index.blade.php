@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'items' => [],
    'reorderable' => true,
    'disabled' => false,
    'required' => false,
    'showError' => true,
])

@php
    $name = $name ?? $attributes->whereStartsWith('wire:model')->first();

    $hasError = false;
    $errorMessage = null;

    if ($showError) {
        if ($error) {
            $hasError = true;
            $errorMessage = $error;
        } elseif ($name && isset($errors) && $errors->has($name)) {
            $hasError = true;
            $errorMessage = $errors->first($name);
        }
    }

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $normalizedItems = collect($items)->map(function ($item, $key) {
        if (is_array($item)) {
            return ['value' => $item['value'] ?? $key, 'label' => $item['label'] ?? $item['value'] ?? $key];
        }
        return ['value' => $key, 'label' => $item];
    })->values()->all();

    $itemsJson = json_encode($normalizedItems, JSON_UNESCAPED_UNICODE);
    $itemsId = \KoreUi\Core\Support\IdContext::secuencia('kore-order-list-items');

    $rowClass = 'flex items-center gap-2 rounded-kore-md border border-kore-border bg-kore-surface px-3 py-2';
    $iconBtnClass = 'inline-flex items-center justify-center size-6 rounded-kore-sm text-kore-muted-fg enabled:hover:bg-kore-muted enabled:hover:text-kore-fg disabled:opacity-30 disabled:cursor-not-allowed transition-colors';
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    {{-- Fuera del `wire:ignore`, para que un cambio de `:items` desde el
         servidor llegue al componente. Dentro del `x-data` se quedaba con los de
         la primera carga: medido, el servidor pasaba de cuatro elementos a cinco
         y la lista seguía enseñando cuatro. Igual que las opciones del select. --}}
    <script type="application/json" id="{{ $itemsId }}" data-kore-order-list-items>{!! $itemsJson !!}</script>

    <div x-data="KoreOrderList({ itemsId: '{{ $itemsId }}' })" wire:ignore class="{{ $disabled ? 'opacity-50 pointer-events-none' : '' }}">
        <input type="hidden" x-ref="hiddenInput" {{ $wireModelAttr }} @if($name) name="{{ $name }}" @endif id="{{ $fieldId }}" />

        <ul @if($reorderable) x-sort="move($item, $position)" @endif class="space-y-1.5">
            <template x-for="(item, index) in orderedItems" :key="item.value">
                <li @if($reorderable) x-sort:item="item.value" @endif class="{{ $rowClass }}">
                    @if($reorderable)
                        <button type="button" x-sort:handle class="shrink-0 cursor-grab text-kore-muted-fg hover:text-kore-fg transition-colors" aria-label="{{ config('kore-ui.ui.translations.drag', 'Arrastrar') }}">
                            <x-lucide-grip-vertical class="size-4" />
                        </button>
                    @endif

                    <span class="flex-1 text-sm text-kore-fg" x-text="item.label"></span>

                    <div class="flex items-center gap-0.5 shrink-0">
                        <button type="button" class="{{ $iconBtnClass }}" x-on:click="moveUp(index)" x-bind:disabled="index === 0" aria-label="{{ config('kore-ui.ui.translations.move_up', 'Subir') }}">
                            <x-lucide-chevron-up class="size-4" />
                        </button>
                        <button type="button" class="{{ $iconBtnClass }}" x-on:click="moveDown(index)" x-bind:disabled="index === orderedItems.length - 1" aria-label="{{ config('kore-ui.ui.translations.move_down', 'Bajar') }}">
                            <x-lucide-chevron-down class="size-4" />
                        </button>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</x-kore::field>
