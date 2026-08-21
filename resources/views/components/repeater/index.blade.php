@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'fields' => [],
    'min' => null,
    'max' => null,
    'addLabel' => null,
    'reorderable' => false,
    'default' => [],
    'disabled' => false,
    'required' => false,
    'showError' => true,
])

@php
    $addLabel = $addLabel ?? config('kore-ui.ui.translations.add', 'Añadir');
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

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    // Un campo sin `key` reventaba aquí con «Undefined array key» apuntando a
    // esta línea del paquete: un 500 en toda la página por un error de quien
    // declara el schema, y sin decir cuál de los campos es. Se comprueba antes.
    foreach ($fields as $indice => $campo) {
        if (! is_array($campo) || ! array_key_exists('key', $campo)) {
            throw new \InvalidArgumentException(
                "kore::repeater: el campo #{$indice} de `fields` no declara `key`. "
                . 'Cada entrada necesita al menos [\'key\' => \'nombre\'].'
            );
        }
    }

    $fieldKeys = array_values(array_map(fn ($f) => $f['key'], $fields));

    $jsConfig = json_encode((object) array_filter([
        'fields' => $fieldKeys,
        'min' => $min ?: null,
        'max' => $max,
        'default' => ! empty($default) ? $default : null,
    ], fn ($v) => $v !== null), JSON_UNESCAPED_UNICODE);

    // Los atributos que el consumidor escribe en la etiqueta y que ningún
    // @props declara —`data-*`, `class`, `style`, `aria-*`, `x-on:*`— no los
    // consumía nadie: se quedaban en el bag y no llegaban al DOM. No daba error,
    // simplemente no existían. Se vuelcan en la raíz del componente.
    // `id` se excluye porque ya lo usa $fieldId sobre el control, y `wire:model`
    // porque vive en el input oculto: duplicarlos daría dos ids iguales y dos
    // enlaces al mismo modelo.
    $atributosRaiz = $attributes->whereDoesntStartWith('wire:model')->except(['id']);
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
        {{ $atributosRaiz->merge(['class' => 'space-y-3 ' . ($disabled ? 'opacity-50 pointer-events-none' : '')]) }}
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
