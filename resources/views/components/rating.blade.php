@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'stars' => 5,
    'allowHalf' => false,
    'readonly' => false,
    'clearable' => true,
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

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));

    $starSize = match($size) {
        'sm' => 'size-4',
        'lg' => 'size-7',
        default => 'size-5',
    };

    $gapClass = match($size) {
        'sm' => 'gap-0.5',
        'lg' => 'gap-1.5',
        default => 'gap-1',
    };

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = json_encode(array_filter([
        'stars' => (int) $stars,
        'allowHalf' => $allowHalf ?: null,
        'readonly' => ($readonly || $disabled) ?: null,
        'clearable' => $clearable ? null : false,
    ], fn($v) => $v !== null));

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
    {{-- `flex-wrap`: con el objetivo táctil en 24×24, diez estrellas pequeñas
         ocupan 240 px y no caben en un móvil de 390. Medido: desbordaba 75 px.
         Que bajen de línea es feo; arrastrar la página de lado, peor. --}}
    <div
        x-data="KoreRating({{ $jsConfig }})"
        @if($readonly)
            role="img"
            aria-label="{{ config('kore-ui.form.translations.rating', 'Valoración') }}"
        @else
            role="radiogroup"
            aria-label="{{ $label ?? config('kore-ui.form.translations.rating', 'Valoración') }}"
        @endif
        {{ $atributosRaiz->merge(['class' => 'inline-flex flex-wrap items-center ' . $gapClass . ' ' . ($disabled ? 'opacity-50 cursor-not-allowed' : '')]) }}
        @if(!$readonly && !$disabled)
            x-on:mouseleave="clearPreview()"
        @endif
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            id="{{ $fieldId }}"
        />

        @for($i = 1; $i <= $stars; $i++)
            <button
                type="button"
                @if($readonly || $disabled) tabindex="-1" aria-hidden="true" @endif
                @if($disabled) disabled @endif
                {{-- `min-size-6` y el `relative` DENTRO, no aquí.

                     Con el `relative` en el botón, la estrella rellena se
                     posiciona con `absolute inset-0` contra la caja del botón,
                     así que el botón no podía ser más grande que la estrella sin
                     descolocarla. Medido: 20×20 en tamaño medio y 16×16 en
                     pequeño, por debajo del mínimo de 24×24 de WCAG 2.2. --}}
                class="inline-flex min-w-6 min-h-6 items-center justify-center cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring rounded-sm {{ $disabled || $readonly ? 'pointer-events-none' : '' }}"
                @if(!$readonly && !$disabled)
                    x-on:click="rate({{ $i }})"
                    x-on:mouseenter="preview({{ $i }})"
                    @if($allowHalf)
                        x-on:mousemove="detectHalf($event, {{ $i }})"
                    @endif
                    role="radio"
                    :aria-checked="displayValue >= {{ $i }} ? 'true' : 'false'"
                    aria-label="{{ $i }} de {{ $stars }} {{ config('kore-ui.form.translations.stars', 'estrellas') }}"
                @endif
            >
                {{-- La caja de la estrella, contra la que se posicionan las
                     capas rellenas y contra la que se mide la media estrella
                     (`data-kore-estrella`). El botón que la envuelve puede ser
                     más grande sin mover nada de esto. --}}
                <span class="relative inline-flex" data-kore-estrella>
                    {{-- Background star (empty) --}}
                    <x-lucide-star class="{{ $starSize }} text-kore-muted-fg/30" />

                    {{-- Foreground star (full) --}}
                    <x-lucide-star
                        class="{{ $starSize }} absolute inset-0 text-kore-warning fill-kore-warning transition-opacity duration-100"
                        x-show="getStarFill({{ $i }}) === 'full'"
                        x-cloak
                    />

                    {{-- Foreground star (half) --}}
                    @if($allowHalf)
                        <x-lucide-star
                            class="{{ $starSize }} absolute inset-0 text-kore-warning fill-kore-warning transition-opacity duration-100"
                            style="clip-path: inset(0 50% 0 0)"
                            x-show="getStarFill({{ $i }}) === 'half'"
                            x-cloak
                        />
                    @endif
                </span>
            </button>
        @endfor
    </div>
</x-kore::field>
