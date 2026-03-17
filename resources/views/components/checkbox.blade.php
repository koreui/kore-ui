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

    $checkboxClasses = collect([
        $checkboxSize,
        'shrink-0 mt-0.5',
        'rounded-kore-sm border appearance-none cursor-pointer',
        'transition-colors duration-150',
        'checked:bg-kore-primary checked:border-kore-primary',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:ring-offset-2',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive' : 'border-kore-input',
        "checked:bg-[url(\"data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e\")] checked:bg-center checked:bg-no-repeat",
    ])->filter()->implode(' ');
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
                    <p class="text-xs text-kore-muted-fg mt-0.5 {{ $disabled ? 'opacity-50' : '' }}">{{ $description }}</p>
                @endif
            </div>
        @endif
    </div>

    @if($hasError && $errorMessage)
        <p class="mt-1 text-sm text-kore-destructive" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
