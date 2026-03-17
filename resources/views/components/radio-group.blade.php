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
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :required="$required"
>
    <div
        role="radiogroup"
        {{ $attributes->only('class')->merge(['class' => $inline ? 'flex flex-wrap gap-4' : 'space-y-2']) }}
    >
        {{ $slot }}
    </div>
</x-kore::field>
