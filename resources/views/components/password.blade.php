@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'icon' => null,
    'toggleable' => null,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');
    $toggleable = $toggleable ?? config('kore-ui.form.password.toggleable', true);

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
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $inputClasses = collect([
        'block w-full rounded-kore-md border bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
        $sizeClasses,
        $icon ? match($size) { 'sm' => 'pl-8', 'lg' => 'pl-11', default => 'pl-10' } : '',
        $toggleable ? match($size) { 'sm' => 'pr-8', 'lg' => 'pr-11', default => 'pr-10' } : '',
    ])->filter()->implode(' ');
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
>
    <div class="relative" x-data="{ show: false }">
        @if($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
            </div>
        @endif

        <input
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'disabled' => $disabled,
                'readonly' => $readonly,
                'required' => $required,
                'autocomplete' => 'current-password',
                'class' => $inputClasses,
            ])->except(['label', 'hint', 'error', 'size', 'icon', 'toggleable', 'show-error']) }}
            x-bind:type="show ? 'text' : 'password'"
        />

        @if($toggleable)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <button
                    type="button"
                    x-on:click="show = !show"
                    class="text-kore-muted-fg hover:text-kore-fg transition-colors focus:outline-none"
                    x-bind:aria-label="show ? 'Hide password' : 'Show password'"
                >
                    <x-lucide-eye x-show="!show" class="{{ $iconSizeClasses }}" />
                    <x-lucide-eye-off x-show="show" x-cloak class="{{ $iconSizeClasses }}" />
                </button>
            </div>
        @endif
    </div>
</x-kore::field>
