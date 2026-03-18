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
    $precision = $precision ?? config('kore-ui.form.number.precision', 2);

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
            @if($name) name="{{ $name }}" @endif id="{{ $fieldId }}" />

        @if($controls)
            <button
                type="button"
                x-on:mousedown="startHold(() => decrement())"
                x-on:mouseup="stopHold()"
                x-on:mouseleave="stopHold()"
                x-on:touchstart.prevent="startHold(() => decrement())"
                x-on:touchend="stopHold()"
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-l-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-minus class="{{ $iconSizeClasses }}" />
            </button>
        @endif

        <input
            type="text"
            x-ref="input"
            class="{{ $currencyInputClasses }}"
            x-on:focus="_onFocus($event)"
            x-on:blur="_onBlur()"
            x-on:input="_onInput($event)"
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            autocomplete="off"
            inputmode="decimal"
        />

        @if($controls)
            <button
                type="button"
                x-on:mousedown="startHold(() => increment())"
                x-on:mouseup="stopHold()"
                x-on:mouseleave="stopHold()"
                x-on:touchstart.prevent="startHold(() => increment())"
                x-on:touchend="stopHold()"
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
            increment() {
                let input = $refs.input;
                let val = parseFloat(input.value) || 0;
                let step = {{ $step }};
                let max = {{ $max !== null ? $max : 'Infinity' }};
                let next = Math.round((val + step) * 1e10) / 1e10;
                if (next <= max) {
                    input.value = next;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },
            decrement() {
                let input = $refs.input;
                let val = parseFloat(input.value) || 0;
                let step = {{ $step }};
                let min = {{ $min !== null ? $min : '-Infinity' }};
                let next = Math.round((val - step) * 1e10) / 1e10;
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
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-l-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-minus class="{{ $iconSizeClasses }}" />
            </button>
        @endif

        <input
            type="number"
            x-ref="input"
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'step' => $step,
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
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
                class="{{ $buttonWidth }} inline-flex items-center justify-center shrink-0 rounded-r-kore-md border border-kore-input bg-kore-bg text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @if($disabled) disabled @endif
            >
                <x-lucide-plus class="{{ $iconSizeClasses }}" />
            </button>
        @endif
    </div>
    @endif
</x-kore::field>
