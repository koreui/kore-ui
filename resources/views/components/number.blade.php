@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'min' => null,
    'max' => null,
    'step' => 1,
    'controls' => true,
    'mode' => 'decimal',
    'currency' => null,
    'locale' => null,
    'precision' => null,
    'prefix' => null,
    'suffix' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $isCurrency = $mode === 'currency';
    $currency = $currency ?? config('kore-ui.form.number.currency', 'USD');
    $locale = $locale ?? config('kore-ui.form.number.locale', null);
    // Auto-derive precision=0 from an integer step when not explicitly set.
    // :step="1" → blocks decimals; :step="0.5" → uses config default.
    // An explicit :precision prop always wins.
    //
    // Pero NO en modo moneda. Ahí `step` es cuánto mueven las flechas —un euro
    // por clic es lo normal— y no tiene nada que ver con cuántos decimales
    // admite el importe. Con el `step` por defecto (1), la deducción dejaba
    // `precision` a 0 en TODA moneda: el campo formateaba sin céntimos y, de
    // paso, `_onKeydown` bloqueaba la tecla del separador decimal, así que no
    // había forma de escribirlos. La documentación promete 2 y ofrece
    // `:precision="0"` para el caso contrario, que es justo al revés.
    $isIntegerStep = is_numeric($step) && fmod((float) $step, 1) === 0.0;
    $precision = $precision ?? (($isCurrency || ! $isIntegerStep)
        ? config('kore-ui.form.number.precision', 2)
        : 0);
    $blockDecimals = (int) $precision === 0;

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

    // Associate the field's hint/error with the control (WCAG 3.3.1 / 4.1.2).
    $describedBy = $hasError ? $fieldId . '-error' : ($hint ? $fieldId . '-hint' : null);

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $iconSizeClasses = match($size) {
        'sm' => 'size-3',
        'lg' => 'size-4.5',
        default => 'size-3.5',
    };

    $buttonWidth = match($size) {
        'sm' => 'w-7',
        'lg' => 'w-10',
        default => 'w-9',
    };

    $inputClasses = collect([
        'block w-full border bg-kore-bg text-kore-fg text-center placeholder:text-kore-muted-fg',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        '[appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
        $controls ? 'rounded-none border-x-0' : 'rounded-kore-md',
        $sizeClasses,
    ])->filter()->implode(' ');

    $currencyInputClasses = collect([
        'block w-full border bg-kore-bg text-kore-fg text-right placeholder:text-kore-muted-fg',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
        $controls ? 'rounded-none border-x-0' : 'rounded-kore-md',
        $sizeClasses,
    ])->filter()->implode(' ');

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = $isCurrency ? json_encode(array_filter([
        'mode' => 'currency',
        'currency' => $currency,
        'locale' => $locale,
        'precision' => (int) $precision,
        'min' => $min !== null ? (float) $min : null,
        'max' => $max !== null ? (float) $max : null,
        'step' => (float) $step,
        'prefix' => $prefix,
        'suffix' => $suffix,
    ], fn($v) => $v !== null)) : '{}';
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    @if($isCurrency)
    {{-- Currency mode: formatted text input + hidden input for wire:model --}}
    <div
        x-data="KoreNumber({{ $jsConfig }})"
        class="flex items-stretch {{ $controls ? 'inline-flex' : '' }}"
    >
        {{-- Hidden input for wire:model (raw numeric value) --}}
        <input type="hidden" x-ref="hiddenInput" {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif />

        @if($controls)
            <button
                type="button"
                x-on:mousedown="startHold(() => decrement())"
                x-on:mouseup="stopHold()"
                x-on:mouseleave="stopHold()"
                x-on:touchstart.prevent="startHold(() => decrement())"
                x-on:touchend="stopHold()"
                aria-label="{{ config('kore-ui.form.translations.decrement', 'Restar') }}"
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-l-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-minus class="{{ $iconSizeClasses }}" />
            </button>
        @endif

        <input
            type="text"
            x-ref="input"
            x-on:focus="_onFocus($event)"
            x-on:blur="_onBlur()"
            x-on:input="_onInput($event)"
            x-on:keydown="_onKeydown($event)"
            autocomplete="off"
            inputmode="{{ $blockDecimals ? 'numeric' : 'decimal' }}"
            {{ $attributes->whereDoesntStartWith('wire:model')->merge([
                'id' => $fieldId,
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'class' => $currencyInputClasses,
            ]) }}
        />

        @if($controls)
            <button
                type="button"
                x-on:mousedown="startHold(() => increment())"
                x-on:mouseup="stopHold()"
                x-on:mouseleave="stopHold()"
                x-on:touchstart.prevent="startHold(() => increment())"
                x-on:touchend="stopHold()"
                aria-label="{{ config('kore-ui.form.translations.increment', 'Sumar') }}"
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-r-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-plus class="{{ $iconSizeClasses }}" />
            </button>
        @endif
    </div>
    @else
    {{-- Decimal mode: standard number input (original behavior) --}}
    <div
        x-data="{
            holdInterval: null,
            holdTimeout: null,
            init() {
                let input = this.$refs.input;
                if (input && this.$wire) {
                    let modelName = input.getAttribute('wire:model.live')
                        || input.getAttribute('wire:model.blur')
                        || input.getAttribute('wire:model.defer')
                        || input.getAttribute('wire:model');
                    if (modelName) {
                        this.$wire.$watch(modelName, (val) => {
                            let current = input.value;
                            let newVal = (val === null || val === undefined || val === '') ? '' : String(val);
                            if (current !== newVal) input.value = newVal;
                        });
                    }
                }
            },
            increment() {
                let input = $refs.input;
                let step = {{ $step }};
                let min = {{ $min !== null ? $min : '0' }};
                let max = {{ $max !== null ? $max : 'Infinity' }};
                let val = parseFloat(input.value);
                let next = isNaN(val) ? min : Math.round((val + step) * 1e10) / 1e10;
                if (next <= max) {
                    input.value = next;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },
            decrement() {
                let input = $refs.input;
                let step = {{ $step }};
                let min = {{ $min !== null ? $min : '-Infinity' }};
                /* Sobre un campo vacío se arranca desde el mismo sitio que
                   increment() —el mínimo, o cero si no hay— y no desde `min`.
                   Con min sin declarar, `min` es -Infinity: el campo recibía
                   «-Infinity», el navegador lo rechaza por no ser un número
                   válido y lo deja vacío, así que pulsar «−» no hacía
                   absolutamente nada mientras «+» sí daba 0. */
                let arranque = {{ $min !== null ? $min : '0' }};
                let val = parseFloat(input.value);
                let next = isNaN(val) ? arranque : Math.round((val - step) * 1e10) / 1e10;
                if (next >= min) {
                    input.value = next;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },
            startHold(fn) {
                fn();
                this.holdTimeout = setTimeout(() => {
                    this.holdInterval = setInterval(() => fn(), 75);
                }, 400);
            },
            stopHold() {
                clearTimeout(this.holdTimeout);
                clearInterval(this.holdInterval);
                this.holdTimeout = null;
                this.holdInterval = null;
            },
            _onKeydown(e) {
                const blocked = {{ $blockDecimals ? "['e','E','.',',']" : "['e','E']" }};
                if (blocked.includes(e.key)) e.preventDefault();
            }
        }"
        class="flex items-stretch {{ $controls ? 'inline-flex' : '' }}"
    >
        @if($controls)
            <button
                type="button"
                x-on:mousedown="startHold(() => decrement())"
                x-on:mouseup="stopHold()"
                x-on:mouseleave="stopHold()"
                x-on:touchstart.prevent="startHold(() => decrement())"
                x-on:touchend="stopHold()"
                aria-label="{{ config('kore-ui.form.translations.decrement', 'Restar') }}"
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-l-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-minus class="{{ $iconSizeClasses }}" />
            </button>
        @endif

        <input
            type="number"
            x-ref="input"
            x-on:keydown="_onKeydown($event)"
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'step' => $step,
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'class' => $inputClasses,
            ])->except(['label', 'hint', 'error', 'size', 'min', 'max', 'controls', 'mode', 'currency', 'locale', 'precision', 'prefix', 'suffix', 'show-error']) }}
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
        />

        @if($controls)
            <button
                type="button"
                x-on:mousedown="startHold(() => increment())"
                x-on:mouseup="stopHold()"
                x-on:mouseleave="stopHold()"
                x-on:touchstart.prevent="startHold(() => increment())"
                x-on:touchend="stopHold()"
                aria-label="{{ config('kore-ui.form.translations.increment', 'Sumar') }}"
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-r-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-plus class="{{ $iconSizeClasses }}" />
            </button>
        @endif
    </div>
    @endif
</x-kore::field>
