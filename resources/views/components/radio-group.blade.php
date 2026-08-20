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
    // Sin `name` esto devolvía null, y sin id no hay `aria-labelledby`: el
    // role="radiogroup" se quedaba sin nombre accesible. Y es justo el caso del
    // ejemplo de la documentación, donde el `wire:model` va en cada radio y el
    // grupo no lleva `name`.
    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));
    $describedBy = $hasError ? ($fieldId ? $fieldId . '-error' : null) : ($hint && $fieldId ? $fieldId . '-hint' : null);
@endphp

<x-kore::field
    :label="$label"
    :hint="$hint"
    :has-error="$hasError"
    :error-message="$errorMessage"
    :field-id="$fieldId"
    :required="$required"
    :labelable="false"
>
    <div
        role="radiogroup"
        @if($fieldId) id="{{ $fieldId }}" @endif
        @if($fieldId && $label) aria-labelledby="{{ $fieldId }}-label" @endif
        @if($hasError) aria-invalid="true" @endif
        @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{-- `only('class')` descartaba todo lo demás: un `data-*`, un `x-on:` o un
             `aria-describedby` escrito en la etiqueta no llegaba al DOM y no
             había forma de saberlo. Solo se quedan fuera el `id` —ya lo lleva el
             grupo— y el `wire:model`, que aquí sirve únicamente para localizar
             el error y no para enlazar nada. --}}
        {{ $attributes->whereDoesntStartWith('wire:model')->except(['id'])->merge(['class' => $inline ? 'flex flex-wrap gap-4' : 'space-y-2']) }}
    >
        {{ $slot }}
    </div>
</x-kore::field>
