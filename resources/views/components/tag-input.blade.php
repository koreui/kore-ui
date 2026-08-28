@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'separator' => ',',
    'max' => null,
    'allowDuplicate' => false,
    'addOnBlur' => true,
    'placeholder' => null,
    'clearable' => false,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');

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

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1 px-2',
        'lg' => 'text-base py-2.5 px-3',
        default => 'text-sm py-1.5 px-2.5',
    };

    $chipSize = match($size) {
        'sm' => 'text-xs px-1.5 py-0.5',
        'lg' => 'text-sm px-2.5 py-1',
        default => 'text-xs px-2 py-0.5',
    };

    $iconSize = match($size) {
        'sm' => 'size-3',
        'lg' => 'size-4',
        default => 'size-3.5',
    };

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $borderClasses = $hasError
        ? 'border-kore-destructive focus-within:ring-kore-destructive/30 focus-within:border-kore-destructive'
        : 'border-kore-input focus-within:ring-kore-ring focus-within:border-kore-primary';

    $jsConfig = json_encode(array_filter([
        'separator' => $separator !== ',' ? $separator : null,
        'max' => $max,
        'allowDuplicate' => $allowDuplicate ?: null,
        'addOnBlur' => !$addOnBlur ? false : null,
    ], fn($v) => $v !== null));

    // Un campo de solo lectura enseña sus etiquetas y las manda al servidor, pero
    // no deja añadir ni quitar ninguna: fuera las «x» de cada chip y la de
    // limpiar, y el input de texto queda `readonly` —sin él, Backspace sobre el
    // campo vacío seguía borrando la última etiqueta—.
    $edicionBloqueada = $disabled || $readonly;

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
        x-data="KoreTagInput({{ $jsConfig }})"
        wire:ignore
        @if($readonly) aria-readonly="true" @endif
        {{ $atributosRaiz->merge(['class' => 'flex flex-wrap items-center gap-1.5 rounded-kore-md border ' . $borderClasses . ' focus-within:ring-2 bg-kore-bg ' . $sizeClasses . ' ' . ($disabled ? 'opacity-50 cursor-not-allowed' : '')]) }}
        x-on:click="$refs.textInput.focus()"
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
        />

        {{-- Tag chips --}}
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center gap-1 rounded-kore-sm bg-kore-primary/10 text-kore-primary-text {{ $chipSize }}">
                <span x-text="tag"></span>
                @if(! $edicionBloqueada)
                    <button
                        type="button"
                        x-on:click.stop="removeTag(index)"
                        x-bind:aria-label="@js(config('kore-ui.form.translations.remove_tag', 'Quitar etiqueta')) + ': ' + tag"
                        class="text-kore-primary/60 hover:text-kore-primary transition-colors"
                    >
                        <x-lucide-x class="{{ $iconSize }}" />
                    </button>
                @endif
            </span>
        </template>

        {{-- Text input --}}
        <input
            type="text"
            x-ref="textInput"
            id="{{ $fieldId }}"
            class="flex-1 min-w-[80px] bg-transparent border-0 outline-none focus:ring-0 p-0 text-kore-fg placeholder:text-kore-muted-fg {{ match($size) { 'sm' => 'text-xs', 'lg' => 'text-base', default => 'text-sm' } }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if(! $edicionBloqueada)
                x-on:keydown="onKeydown($event)"
                x-on:paste="onPaste($event)"
                @if($addOnBlur)
                    x-on:blur="addCurrentTag()"
                @endif
            @endif
        />

        {{-- Clear all button --}}
        @if($clearable && ! $edicionBloqueada)
            <button
                type="button"
                x-show="tags.length > 0"
                x-cloak
                x-on:click.stop="clearAll()"
                class="ml-auto text-kore-muted-fg hover:text-kore-fg transition-colors shrink-0"
            >
                <x-lucide-x class="{{ $iconSize }}" />
            </button>
        @endif
    </div>
</x-kore::field>
