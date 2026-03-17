@props([
    'label' => null,
    'description' => null,
    'size' => null,
    'labelPosition' => 'right',
    'onLabel' => null,
    'offLabel' => null,
    'disabled' => false,
    'name' => null,
    'error' => null,
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

    $trackSize = match($size) {
        'sm' => 'h-5 w-9',
        'lg' => 'h-7 w-14',
        default => 'h-6 w-11',
    };

    $thumbSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5.5',
        default => 'size-4.5',
    };

    $thumbTranslate = match($size) {
        'sm' => 'translate-x-4',
        'lg' => 'translate-x-7',
        default => 'translate-x-5',
    };

    $labelSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $onOffSize = match($size) {
        'sm' => 'text-[9px]',
        'lg' => 'text-xs',
        default => 'text-[10px]',
    };
@endphp

<div
    class="kore-toggle"
    x-data="{ on: false }"
    x-init="on = $refs.checkbox.checked; $watch('on', v => { $refs.checkbox.checked = v; $refs.checkbox.dispatchEvent(new Event('change', { bubbles: true })) })"
>
    <div class="flex items-start gap-3 {{ $labelPosition === 'left' ? 'flex-row-reverse justify-end' : '' }}">
        {{-- Hidden checkbox for wire:model --}}
        <input
            type="checkbox"
            x-ref="checkbox"
            x-on:change="on = $refs.checkbox.checked"
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'disabled' => $disabled,
            ])->except(['label', 'description', 'size', 'label-position', 'on-label', 'off-label', 'error', 'show-error']) }}
            class="sr-only"
        />

        {{-- Toggle switch --}}
        <button
            type="button"
            role="switch"
            x-bind:aria-checked="on.toString()"
            x-on:click="if (!{{ $disabled ? 'true' : 'false' }}) on = !on"
            class="{{ $trackSize }} relative inline-flex shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-kore-ring focus:ring-offset-2 {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            x-bind:class="on ? 'bg-kore-primary' : 'bg-kore-muted'"
        >
            <span class="sr-only">{{ $label }}</span>

            {{-- On/off labels inside track --}}
            @if($onLabel || $offLabel)
                <span
                    class="absolute inset-0 flex items-center {{ $onOffSize }} font-medium text-kore-primary-fg"
                    x-show="on"
                >
                    <span class="ml-1.5">{{ $onLabel }}</span>
                </span>
                <span
                    class="absolute inset-0 flex items-center justify-end {{ $onOffSize }} font-medium text-kore-muted-fg"
                    x-show="!on"
                >
                    <span class="mr-1.5">{{ $offLabel }}</span>
                </span>
            @endif

            {{-- Thumb --}}
            <span
                class="{{ $thumbSize }} pointer-events-none inline-block transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                x-bind:class="on ? '{{ $thumbTranslate }}' : 'translate-x-0'"
            ></span>
        </button>

        @if($label || $description)
            <div class="select-none">
                @if($label)
                    <label
                        for="{{ $fieldId }}"
                        class="{{ $labelSize }} font-medium text-kore-fg cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                        x-on:click="if (!{{ $disabled ? 'true' : 'false' }}) on = !on"
                    >
                        {{ $label }}
                    </label>
                @endif
                @if($description)
                    <p class="text-xs text-kore-muted-fg mt-0.5 {{ $disabled ? 'opacity-50' : '' }}">{{ $description }}</p>
                @endif
            </div>
        @endif
    </div>

    @if($hasError && $errorMessage)
        <p class="mt-1 text-sm text-kore-destructive" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
