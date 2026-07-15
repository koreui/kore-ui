@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'keyPlaceholder' => 'Clave',
    'valuePlaceholder' => 'Valor',
    'addLabel' => 'Añadir',
    'addable' => true,
    'deletable' => true,
    'reorderable' => false,
    'max' => null,
    'disabled' => false,
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

    $fieldId = $attributes->get('id', $name ? 'kore-' . str_replace('.', '-', $name) : 'kore-' . uniqid());

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $inputSizeClasses = match($size) {
        'sm' => 'text-xs py-1 px-2',
        'lg' => 'text-base py-2.5 px-3',
        default => 'text-sm py-1.5 px-2.5',
    };

    $borderClasses = $hasError
        ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive'
        : 'border-kore-input focus:ring-kore-ring focus:border-kore-primary';

    $inputClasses = "w-full rounded-kore-md border {$borderClasses} bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg focus:ring-2 outline-none transition-colors {$inputSizeClasses}";

    $jsConfig = json_encode((object) array_filter([
        'max' => $max,
    ], fn($v) => $v !== null));
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
        x-data="KoreKeyValue({{ $jsConfig }})"
        wire:ignore
        class="space-y-2 {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}"
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
        <div @if($reorderable) x-sort="movePair($item, $position)" @endif class="space-y-2">
            <template x-for="(pair, index) in pairs" :key="index">
                <div class="flex items-center gap-2" @if($reorderable) x-sort:item="index" @endif>
                    @if($reorderable)
                        <button type="button" x-sort:handle class="shrink-0 cursor-grab text-kore-muted-fg hover:text-kore-fg">
                            <x-lucide-grip-vertical class="size-4" />
                        </button>
                    @endif

                    <input
                        type="text"
                        x-model="pair.key"
                        x-on:change="_sync()"
                        placeholder="{{ $keyPlaceholder }}"
                        @if($disabled) disabled @endif
                        class="{{ $inputClasses }}"
                    />

                    <input
                        type="text"
                        x-model="pair.value"
                        x-on:change="_sync()"
                        placeholder="{{ $valuePlaceholder }}"
                        @if($disabled) disabled @endif
                        class="{{ $inputClasses }}"
                    />

                    @if($deletable && !$disabled)
                        <button
                            type="button"
                            x-on:click="removePair(index)"
                            class="shrink-0 text-kore-muted-fg hover:text-kore-destructive transition-colors"
                            aria-label="Eliminar fila"
                        >
                            <x-lucide-x class="size-4" />
                        </button>
                    @endif
                </div>
            </template>
        </div>

        {{-- Add button --}}
        @if($addable && !$disabled)
            <div @if($max) x-show="pairs.length < {{ (int) $max }}" @endif>
                <x-kore::button
                    type="button"
                    x-on:click="addPair()"
                    :label="$addLabel"
                    icon="plus"
                    variant="outline"
                    size="sm"
                />
            </div>
        @endif
    </div>
</x-kore::field>
