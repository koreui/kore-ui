@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'colors' => null,
    'allowCustom' => null,
    'inline' => false,
    'clearable' => true,
    'columns' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    // Era «Select color» dentro de la expresión de Alpine, sin forma de
    // traducirlo sin publicar la vista.
    $textoVacio = config('kore-ui.form.translations.pick_color', 'Elegir color');
    $size = $size ?? config('kore-ui.form.size', 'md');
    $columns = $columns ?? config('kore-ui.form.color_picker.columns', 8);
    // `null` y no `true` en el @props: con un valor escrito, el `??` no
    // dispara nunca y `allow_custom` de la configuración no se aplica.
    $allowCustom = $allowCustom ?? config('kore-ui.form.color_picker.allow_custom', true);

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

    $defaultColors = [
        '#ef4444', '#f97316', '#f59e0b', '#eab308',
        '#84cc16', '#22c55e', '#10b981', '#14b8a6',
        '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1',
        '#8b5cf6', '#a855f7', '#d946ef', '#ec4899',
        '#f43f5e', '#78716c', '#737373', '#000000',
        '#ffffff', '#94a3b8', '#64748b', '#475569',
    ];

    $palette = $colors ?? $defaultColors;

    // Ancho máximo, no ancho fijo: la rejilla reparte `$columns` columnas con
    // `1fr`, pero una muestra de tamaño fijo no puede encogerse por debajo de
    // ese tamaño, así que ocho columnas imponían un mínimo de unos 266 px y
    // desbordaban cualquier contenedor más estrecho —una columna de un
    // formulario a dos columnas en un móvil, por ejemplo—. Con `w-full` y un
    // tope, la muestra se encoge cuando hace falta y mantiene el cuadrado.
    $swatchDim = match($size) {
        'sm' => 'w-full max-w-6 aspect-square',
        'lg' => 'w-full max-w-9 aspect-square',
        default => 'w-full max-w-7 aspect-square',
    };

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $iconSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $borderClasses = $hasError
        ? 'border-kore-destructive focus-within:ring-kore-destructive/30'
        : 'border-kore-input';

    $jsConfig = json_encode(array_filter([
        'inline' => $inline ?: null,
    ], fn($v) => $v !== null));

    // Compute contrast for each swatch (PHP side, since colors are static)
    $isLightColor = function($hex) {
        $c = ltrim($hex, '#');
        if (strlen($c) === 3) $c = $c[0].$c[0].$c[1].$c[1].$c[2].$c[2];
        $r = hexdec(substr($c, 0, 2));
        $g = hexdec(substr($c, 2, 2));
        $b = hexdec(substr($c, 4, 2));
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 > 0.6;
    };

    // En solo lectura el color se ve y se envía, pero el panel no se abre: sin
    // esto el disparador seguía desplegando la paleta y cualquier muestra
    // cambiaba el valor. Se quedan el foco y la muestra —para leerlo y copiarlo—
    // y se van el chevrón, que promete un despliegue que ya no ocurre, y la «x».
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
        x-data="KoreColorPicker({{ $jsConfig }})"
        x-on:keydown.escape="onEscape($event)"
        @if($readonly) aria-readonly="true" @endif
        {{ $atributosRaiz->merge(['class' => $disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : '']) }}
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            id="{{ $fieldId }}"
        />

        @if(!$inline)
            {{-- Dropdown trigger --}}
            <div
                x-ref="trigger"
                role="button"
                tabindex="0"
                @if(! $edicionBloqueada)
                    x-on:click="toggle()"
                    x-on:keydown.enter.prevent="toggle()"
                    x-on:keydown.space.prevent="toggle()"
                @endif
                class="flex items-center gap-2 w-full rounded-kore-md border {{ $borderClasses }} {{ $sizeClasses }} bg-kore-bg text-kore-fg transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary {{ $disabled ? 'pointer-events-none' : '' }}"
            >
                {{-- Color preview swatch --}}
                <span
                    class="size-5 rounded-kore-sm border border-kore-border shrink-0"
                    :style="value ? 'background-color:' + value : ''"
                    :class="!value && 'bg-kore-muted'"
                ></span>

                {{-- Hex text --}}
                <span class="flex-1 text-left truncate" x-text="value || @js($textoVacio)"></span>

                {{-- Clearable --}}
                @if($clearable && ! $edicionBloqueada)
                    <span
                        x-show="value"
                        x-cloak
                        x-on:click.stop="clear()"
                        role="button"
                        class="text-kore-muted-fg hover:text-kore-fg transition-colors shrink-0 cursor-pointer"
                    >
                        <x-lucide-x class="{{ $iconSize }}" />
                    </span>
                @endif

                {{-- Chevron --}}
                @if(! $edicionBloqueada)
                <x-lucide-chevron-down class="{{ $iconSize }} text-kore-muted-fg shrink-0 transition-transform duration-150" ::class="open && 'rotate-180'" />
                @endif
            </div>
        @endif

        @if($inline)
            {{-- Inline panel --}}
            <div class="rounded-kore-md border border-kore-border bg-kore-bg p-3 {{ $edicionBloqueada ? 'pointer-events-none' : '' }}">
                {{-- Swatch grid --}}
                <div class="grid gap-1.5" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr))">
                    @foreach($palette as $hex)
                        @php $checkColor = $isLightColor($hex) ? 'text-black' : 'text-white'; @endphp
                        <button
                            type="button"
                            class="{{ $swatchDim }} rounded-kore-sm border border-kore-border/50 flex items-center justify-center transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-kore-ring"
                            style="background-color: {{ $hex }}"
                            x-on:click="selectColor('{{ $hex }}')"
                            aria-label="{{ config('kore-ui.form.translations.pick_color', 'Elegir color') }} {{ $hex }}"
                            x-bind:aria-pressed="value === @js($hex) ? 'true' : 'false'"
                        >
                            <x-lucide-check
                                class="size-3.5 {{ $checkColor }}"
                                x-show="value === '{{ $hex }}'"
                                x-cloak
                            />
                        </button>
                    @endforeach
                </div>

                @if($allowCustom)
                    <div class="mt-3 pt-3 border-t border-kore-border flex items-center gap-2">
                        <span
                            class="size-8 rounded-kore-sm border border-kore-border shrink-0"
                            :style="isValidHex(customHex) ? 'background-color:' + (customHex.startsWith('#') ? customHex : '#' + customHex) : ''"
                        ></span>
                        <input
                            type="text"
                            x-model="customHex"
                            placeholder="#000000"
                            aria-label="{{ config('kore-ui.form.translations.custom_color', 'Color personalizado') }}"
                            class="flex-1 min-w-0 text-sm bg-kore-bg text-kore-fg border border-kore-input rounded-kore-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                            x-on:keydown.enter.prevent="applyCustom()"
                            maxlength="7"
                        />
                        <button
                            type="button"
                            x-on:click="applyCustom()"
                            class="text-xs font-medium px-2 py-1 rounded-kore-sm bg-kore-primary text-kore-primary-fg hover:opacity-90 transition-opacity"
                        >
                            Apply
                        </button>
                    </div>
                @endif
            </div>
        @else
            {{-- Dropdown panel (teleported) --}}
            <template x-teleport="body">
                <div
                    data-kore-teleport
                    x-ref="dropdown"
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    {{-- También aquí: el panel está teleportado a `<body>`, así
                         que las teclas pulsadas con el foco dentro no burbujean
                         hasta la raíz que lleva el otro manejador. --}}
                    x-on:keydown.escape="onEscape($event)"
                    class="rounded-kore-md border border-kore-border bg-kore-bg shadow-lg p-3 z-50"
                    style="position: fixed"
                >
                    {{-- Swatch grid --}}
                    <div class="grid gap-1.5" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr))">
                        @foreach($palette as $hex)
                            @php $checkColor = $isLightColor($hex) ? 'text-black' : 'text-white'; @endphp
                            <button
                                type="button"
                                class="{{ $swatchDim }} rounded-kore-sm border border-kore-border/50 flex items-center justify-center transition-transform hover:scale-110 focus:outline-none focus:ring-2 focus:ring-kore-ring"
                                style="background-color: {{ $hex }}"
                                x-on:click="selectColor('{{ $hex }}')"
                                aria-label="{{ config('kore-ui.form.translations.pick_color', 'Elegir color') }} {{ $hex }}"
                                x-bind:aria-pressed="value === @js($hex) ? 'true' : 'false'"
                            >
                                <x-lucide-check
                                    class="size-3.5 {{ $checkColor }}"
                                    x-show="value === '{{ $hex }}'"
                                    x-cloak
                                />
                            </button>
                        @endforeach
                    </div>

                    @if($allowCustom)
                        <div class="mt-3 pt-3 border-t border-kore-border flex items-center gap-2">
                            <span
                                class="size-8 rounded-kore-sm border border-kore-border shrink-0"
                                :style="isValidHex(customHex) ? 'background-color:' + (customHex.startsWith('#') ? customHex : '#' + customHex) : ''"
                            ></span>
                            <input
                                type="text"
                                x-model="customHex"
                                placeholder="#000000"
                                aria-label="{{ config('kore-ui.form.translations.custom_color', 'Color personalizado') }}"
                                class="flex-1 min-w-0 text-sm bg-kore-bg text-kore-fg border border-kore-input rounded-kore-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary"
                                x-on:keydown.enter.prevent="applyCustom()"
                                maxlength="7"
                            />
                            <button
                                type="button"
                                x-on:click="applyCustom()"
                                class="text-xs font-medium px-2 py-1 rounded-kore-sm bg-kore-primary text-kore-primary-fg hover:opacity-90 transition-opacity"
                            >
                                Apply
                            </button>
                        </div>
                    @endif
                </div>
            </template>
        @endif
    </div>
</x-kore::field>
