@props([
    'label' => null,
    'hint' => null,
    'inline' => false,
    'error' => null,
    'name' => null,
    'required' => false,
    'showError' => true,
])

@php
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

    // Group-level id so field.blade.php can id the hint/error and the radiogroup
    // can reference them via aria-describedby (WCAG 3.3.1 / 4.1.2).
    $fieldId = $attributes->get('id', $name ? 'kore-' . str_replace('.', '-', $name) : null);
    $describedBy = $hasError ? ($fieldId ? $fieldId . '-error' : null) : ($hint && $fieldId ? $fieldId . '-hint' : null);
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
        role="radiogroup"
        @if($hasError) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->only('class')->merge(['class' => $inline ? 'flex flex-wrap gap-4' : 'space-y-2']) }}
    >
        {{ $slot }}
    </div>
</x-kore::field>
