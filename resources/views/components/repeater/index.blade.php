@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'fields' => [],
    'min' => null,
    'max' => null,
    'addLabel' => 'Añadir',
    'reorderable' => false,
    'default' => [],
    'disabled' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $min = $min ?? config('kore-ui.form.repeater.min', 0);
    $max = $max ?? config('kore-ui.form.repeater.max', null);

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

    $fieldId = $attributes->get('id', $name ? 'kore-' . str_replace('.', '-', $name) : 'kore-' . uniqid());

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $fieldKeys = array_values(array_map(fn ($f) => $f['key'], $fields));

    $jsConfig = json_encode((object) array_filter([
        'fields' => $fieldKeys,
        'min' => $min ?: null,
        'max' => $max,
        'default' => ! empty($default) ? $default : null,
    ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE);
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
        x-data="KoreRepeater({{ $jsConfig }})"
        wire:ignore
        class="space-y-3 {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}"
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            id="{{ $fieldId }}"
        />

        {{-- Rows --}}
        <div @if($reorderable) x-sort="moveRow($item, $position)" @endif class="space-y-3">
            <template x-for="(row, index) in rows" :key="index">
                <x-kore::repeater.item
                    :fields="$fields"
                    :reorderable="$reorderable"
                    :deletable="! $disabled"
                />
            </template>
        </div>

        {{-- Empty state --}}
        <template x-if="rows.length === 0">
            <p class="text-sm text-kore-muted-fg py-2">Sin filas. Añade la primera.</p>
        </template>

        {{-- Add button --}}
        @if(! $disabled)
            <div @if($max) x-show="rows.length < {{ (int) $max }}" @endif>
                <x-kore::button
                    type="button"
                    x-on:click="addRow()"
                    :label="$addLabel"
                    icon="plus"
                    variant="outline"
                    size="sm"
                />
            </div>
        @endif
    </div>
</x-kore::field>
