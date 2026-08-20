@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'min' => 0,
    'max' => 100,
    'step' => 1,
    'range' => false,
    'showValue' => false,
    'showLabels' => false,
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

    $trackHeight = match($size) {
        'sm' => 'h-1',
        'lg' => 'h-3',
        default => 'h-2',
    };

    $trackPx = match($size) {
        'sm' => '4px',
        'lg' => '12px',
        default => '8px',
    };

    $thumbVar = match($size) {
        'sm' => '14px',
        'lg' => '22px',
        default => '18px',
    };

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = json_encode([
        'min' => (float) $min,
        'max' => (float) $max,
        'step' => (float) $step,
        'single' => !$range,
    ]);
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    @if($range)
        {{-- Range mode (two handles) --}}
        <div
            x-data="KoreRange({{ $jsConfig }})"
            class="w-full {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            style="--kore-thumb-size: {{ $thumbVar }}; --kore-track-height: {{ $trackPx }}"
        >
            {{-- Hidden input for wire:model --}}
            <input
                type="hidden"
                x-ref="hiddenInput"
                {{ $wireModelAttr }}
                @if($name) name="{{ $name }}" @endif
                id="{{ $fieldId }}"
            />

            @if($showValue)
                <div class="flex justify-between mb-1 text-sm text-kore-fg font-medium">
                    <span x-text="low"></span>
                    <span x-text="high"></span>
                </div>
            @endif

            <div class="relative w-full" style="height: var(--kore-thumb-size)">
                {{-- Track background --}}
                <div class="absolute top-1/2 -translate-y-1/2 left-0 right-0 {{ $trackHeight }} bg-kore-muted rounded-full"></div>

                {{-- Filled track --}}
                <div
                    class="absolute top-1/2 -translate-y-1/2 {{ $trackHeight }} bg-kore-primary rounded-full pointer-events-none"
                    :style="filledTrackStyle"
                ></div>

                {{-- Low handle --}}
                <input
                    type="range"
                    class="kore-range-input absolute inset-0 w-full pointer-events-auto z-10"
                    min="{{ $min }}"
                    max="{{ $max }}"
                    step="{{ $step }}"
                    x-model.number="low"
                    x-on:input="onLowInput()"
                    aria-label="{{ trim(($label ? $label . ' — ' : '') . config('kore-ui.form.translations.range_min', 'mínimo')) }}"
                    {{ $disabled ? 'disabled' : '' }}
                />

                {{-- High handle --}}
                <input
                    type="range"
                    class="kore-range-input absolute inset-0 w-full pointer-events-auto z-20"
                    min="{{ $min }}"
                    max="{{ $max }}"
                    step="{{ $step }}"
                    x-model.number="high"
                    x-on:input="onHighInput()"
                    aria-label="{{ trim(($label ? $label . ' — ' : '') . config('kore-ui.form.translations.range_max', 'máximo')) }}"
                    {{ $disabled ? 'disabled' : '' }}
                />
            </div>

            @if($showLabels)
                <div class="flex justify-between mt-1 text-xs text-kore-muted-fg">
                    <span>{{ $min }}</span>
                    <span>{{ $max }}</span>
                </div>
            @endif
        </div>
    @else
        {{-- Single mode --}}
        <div
            x-data="KoreRange({{ $jsConfig }})"
            class="w-full {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            style="--kore-thumb-size: {{ $thumbVar }}; --kore-track-height: {{ $trackPx }}"
        >
            @if($showValue)
                <div class="flex justify-end mb-1 text-sm text-kore-fg font-medium">
                    <span x-text="value"></span>
                </div>
            @endif

            <div class="relative w-full" style="height: var(--kore-thumb-size)">
                {{-- Track background --}}
                <div class="absolute top-1/2 -translate-y-1/2 left-0 right-0 {{ $trackHeight }} bg-kore-muted rounded-full"></div>

                {{-- Filled track --}}
                <div
                    class="absolute top-1/2 -translate-y-1/2 left-0 {{ $trackHeight }} bg-kore-primary rounded-full pointer-events-none"
                    :style="'width:' + percent + '%'"
                ></div>

                <input
                    type="range"
                    x-ref="input"
                    class="kore-range-input absolute inset-0 w-full"
                    {{ $attributes->merge([
                        'id' => $fieldId,
                        'name' => $name,
                        'min' => $min,
                        'max' => $max,
                        'step' => $step,
                        'disabled' => $disabled,
                        'required' => $required,
                    ])->except(['label', 'hint', 'error', 'size', 'range', 'show-value', 'show-labels', 'show-error']) }}
                    x-model.number="value"
                />
            </div>

            @if($showLabels)
                <div class="flex justify-between mt-1 text-xs text-kore-muted-fg">
                    <span>{{ $min }}</span>
                    <span>{{ $max }}</span>
                </div>
            @endif
        </div>
    @endif
</x-kore::field>
