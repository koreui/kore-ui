@props([
    'label' => null,
    'description' => null,
    'size' => null,
    'labelPosition' => 'right',
    'indeterminate' => false,
    'disabled' => false,
    'name' => null,
    'error' => null,
    'showError' => true,
    // `false` apaga las transiciones de este componente y de su panel. Ver
    // KoreUi\Core\Support\Motion.
    'animated' => null,
])

@php $attributes = \KoreUi\Core\Support\Motion::marcar($attributes, $animated); @endphp

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

    // Associate the error/description with the control (WCAG 3.3.1 / 4.1.2).
    $describedBy = $hasError ? $fieldId . '-error' : ($description ? $fieldId . '-description' : null);

    $checkboxSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $labelSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    // Todo el aspecto —borde, palomita, guion de «mixto», foco, error— vive en
    // la utilidad `kore-checkmark` de kore-theme.css: la comparten la selección
    // de filas de la tabla, el árbol y el transfer.
    $checkboxClasses = $checkboxSize . ' mt-0.5 kore-checkmark';
@endphp

<div
    class="kore-checkbox"
    @if($indeterminate)
        x-data
        x-init="$el.querySelector('input[type=checkbox]').indeterminate = true"
    @endif
>
    <div class="flex items-start gap-2 {{ $labelPosition === 'left' ? 'flex-row-reverse justify-end' : '' }}">
        <input
            type="checkbox"
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'disabled' => $disabled,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'class' => $checkboxClasses,
            ])->except(['label', 'description', 'size', 'label-position', 'indeterminate', 'error', 'show-error']) }}
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
        <p @if($fieldId) id="{{ $fieldId }}-error" @endif class="mt-1 text-sm text-kore-destructive-text" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
