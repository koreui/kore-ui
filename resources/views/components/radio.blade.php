@props([
    'label' => null,
    'description' => null,
    'value' => null,
    'size' => null,
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

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name) . '-' . ($value ?? ''));

    // Associate the error/description with the control (WCAG 3.3.1 / 4.1.2).
    $describedBy = $hasError ? $fieldId . '-error' : ($description ? $fieldId . '-description' : null);

    $radioSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $labelSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $radioClasses = collect([
        $radioSize,
        'shrink-0 mt-0.5',
        'rounded-full border appearance-none cursor-pointer',
        'transition-colors duration-150',
        'checked:border-[5px] checked:border-kore-primary',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:ring-offset-2 ring-offset-kore-bg',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive' : 'border-kore-input',
    ])->filter()->implode(' ');
@endphp

<div class="kore-radio">
    <div class="flex items-start gap-2">
        <input
            type="radio"
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'value' => $value,
                'disabled' => $disabled,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'class' => $radioClasses,
            ])->except(['label', 'description', 'size', 'error', 'show-error']) }}
        />

        @if($label || $description)
            <div class="select-none">
                @if($label)
                    <label for="{{ $fieldId }}" class="{{ $labelSize }} font-medium text-kore-fg cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
                        {{ $label }}
                    </label>
                @endif
                @if($description)
                    <p @if($fieldId) id="{{ $fieldId }}-description" @endif class="text-xs text-kore-muted-fg mt-0.5 {{ $disabled ? 'opacity-50' : '' }}">{{ $description }}</p>
                @endif
            </div>
        @endif
    </div>

    @if($hasError && $errorMessage)
        <p @if($fieldId) id="{{ $fieldId }}-error" @endif class="mt-1 text-sm text-kore-destructive" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
