@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'items' => [],
    'searchable' => null,
    'titles' => null,
    'disabled' => false,
    'required' => false,
    'showError' => true,
])

@php
    $searchable = $searchable ?? config('kore-ui.ui.transfer.searchable', true);
    $titles = $titles ?? ['Disponibles', 'Seleccionados'];

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

    // Normalize items to [{value, label}]
    $normalizedItems = collect($items)->map(function ($item, $key) {
        if (is_array($item)) {
            return ['value' => $item['value'] ?? $key, 'label' => $item['label'] ?? $item['value'] ?? $key];
        }
        return ['value' => $key, 'label' => $item];
    })->values()->all();

    $jsConfig = json_encode((object) ['items' => $normalizedItems], JSON_UNESCAPED_UNICODE);

    $panelClass = 'flex flex-col rounded-kore-lg border border-kore-border bg-kore-surface overflow-hidden';
    $searchClass = 'w-full border-0 border-b border-kore-border bg-transparent px-3 py-2 text-sm text-kore-fg placeholder:text-kore-muted-fg focus:ring-0 outline-none';
    $rowClass = 'flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-kore-muted/50 transition-colors';
    $moveBtnClass = 'inline-flex items-center justify-center size-8 rounded-kore-md border border-kore-border text-kore-muted-fg enabled:hover:bg-kore-muted enabled:hover:text-kore-fg disabled:opacity-40 disabled:cursor-not-allowed transition-colors';
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    <div
        x-data="KoreTransfer({{ $jsConfig }})"
        wire:ignore
        class="grid grid-cols-[1fr_auto_1fr] items-stretch gap-3 {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}"
    >
        <input type="hidden" x-ref="hiddenInput" {{ $wireModelAttr }} @if($name) name="{{ $name }}" @endif id="{{ $fieldId }}" />

        {{-- Source panel --}}
        <div class="{{ $panelClass }}">
            <div class="flex items-center justify-between px-3 py-2 border-b border-kore-border bg-kore-muted/40">
                <span class="text-xs font-medium text-kore-muted-fg">{{ $titles[0] }}</span>
                <span class="text-xs text-kore-muted-fg" x-text="availableCount"></span>
            </div>
            @if($searchable)
                <input type="text" x-model="sourceSearch" placeholder="Buscar..." class="{{ $searchClass }}" />
            @endif
            <ul class="flex-1 overflow-y-auto max-h-64 divide-y divide-kore-border">
                <template x-for="item in sourceItems" :key="item.value">
                    <li class="{{ $rowClass }}" :class="isChecked('source', item.value) && 'bg-kore-primary/5'" x-on:click="toggleCheck('source', item.value)">
                        <input type="checkbox" :checked="isChecked('source', item.value)" class="pointer-events-none rounded-kore-sm border-kore-input text-kore-primary" />
                        <span class="text-kore-fg" x-text="item.label"></span>
                    </li>
                </template>
                <template x-if="sourceItems.length === 0">
                    <li class="px-3 py-4 text-center text-xs text-kore-muted-fg">Sin elementos</li>
                </template>
            </ul>
        </div>

        {{-- Move buttons --}}
        <div class="flex flex-col justify-center gap-2">
            <button type="button" class="{{ $moveBtnClass }}" x-on:click="moveAllToTarget()" aria-label="Mover todo a seleccionados">
                <x-lucide-chevrons-right class="size-4" />
            </button>
            <button type="button" class="{{ $moveBtnClass }}" x-on:click="moveToTarget()" x-bind:disabled="checkedSource.length === 0" aria-label="Mover seleccionados">
                <x-lucide-chevron-right class="size-4" />
            </button>
            <button type="button" class="{{ $moveBtnClass }}" x-on:click="moveToSource()" x-bind:disabled="checkedTarget.length === 0" aria-label="Quitar seleccionados">
                <x-lucide-chevron-left class="size-4" />
            </button>
            <button type="button" class="{{ $moveBtnClass }}" x-on:click="moveAllToSource()" aria-label="Quitar todo">
                <x-lucide-chevrons-left class="size-4" />
            </button>
        </div>

        {{-- Target panel --}}
        <div class="{{ $panelClass }}">
            <div class="flex items-center justify-between px-3 py-2 border-b border-kore-border bg-kore-muted/40">
                <span class="text-xs font-medium text-kore-muted-fg">{{ $titles[1] }}</span>
                <span class="text-xs text-kore-muted-fg" x-text="selectedCount"></span>
            </div>
            @if($searchable)
                <input type="text" x-model="targetSearch" placeholder="Buscar..." class="{{ $searchClass }}" />
            @endif
            <ul class="flex-1 overflow-y-auto max-h-64 divide-y divide-kore-border">
                <template x-for="item in targetItems" :key="item.value">
                    <li class="{{ $rowClass }}" :class="isChecked('target', item.value) && 'bg-kore-primary/5'" x-on:click="toggleCheck('target', item.value)">
                        <input type="checkbox" :checked="isChecked('target', item.value)" class="pointer-events-none rounded-kore-sm border-kore-input text-kore-primary" />
                        <span class="text-kore-fg" x-text="item.label"></span>
                    </li>
                </template>
                <template x-if="targetItems.length === 0">
                    <li class="px-3 py-4 text-center text-xs text-kore-muted-fg">Sin elementos</li>
                </template>
            </ul>
        </div>
    </div>
</x-kore::field>
