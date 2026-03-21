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

    // Full class string so Tailwind v4 scanner picks them up
    $thumbCheckedClass = match($size) {
        'sm' => 'peer-checked:translate-x-4',
        'lg' => 'peer-checked:translate-x-7',
        default => 'peer-checked:translate-x-5',
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

<div class="kore-toggle">
    <div class="flex items-start gap-3 {{ $labelPosition === 'left' ? 'flex-row-reverse justify-end' : '' }}">

        {{-- Visual track: label wraps the native checkbox --}}
        <label
            class="{{ $trackSize }} relative inline-flex shrink-0 rounded-full border-2 border-transparent
                   bg-kore-muted has-[:checked]:bg-kore-primary
                   transition-colors duration-200 ease-in-out
                   focus-within:ring-2 focus-within:ring-kore-ring focus-within:ring-offset-2
                   {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
        >
            <input
                type="checkbox"
                {{ $attributes->merge([
                    'id' => $fieldId,
                    'name' => $name,
                    'disabled' => $disabled,
                    'class' => 'sr-only peer',
                ])->except(['label', 'description', 'size', 'label-position', 'on-label', 'off-label', 'error', 'show-error']) }}
            />

            {{-- On/off labels inside track --}}
            @if($onLabel || $offLabel)
                <span class="absolute inset-0 hidden peer-checked:flex items-center {{ $onOffSize }} font-medium text-kore-primary-fg">
                    <span class="ml-1.5">{{ $onLabel }}</span>
                </span>
                <span class="absolute inset-0 flex peer-checked:hidden items-center justify-end {{ $onOffSize }} font-medium text-kore-muted-fg">
                    <span class="mr-1.5">{{ $offLabel }}</span>
                </span>
            @endif

            {{-- Thumb --}}
            <span class="{{ $thumbSize }} {{ $thumbCheckedClass }} pointer-events-none inline-block transform rounded-full bg-white shadow-sm ring-0 translate-x-0 transition duration-200 ease-in-out"></span>
        </label>

        @if($label || $description)
            <div class="select-none">
                @if($label)
                    <label
                        for="{{ $fieldId }}"
                        class="{{ $labelSize }} font-medium text-kore-fg {{ $disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
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
