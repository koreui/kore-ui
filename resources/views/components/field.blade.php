@props([
    'label' => null,
    'hint' => null,
    'hasError' => false,
    'errorMessage' => null,
    'fieldId' => null,
    'required' => false,
    'inline' => false,
])

<div {{ $attributes->only('class')->merge(['class' => 'kore-field']) }}>
    @if($label)
        <label
            @if($fieldId) for="{{ $fieldId }}" @endif
            class="block text-sm font-medium text-kore-fg mb-1"
        >
            {{ $label }}
            @if($required)
                <span class="text-kore-destructive ml-0.5">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hasError && $errorMessage)
        <p class="mt-1 text-sm text-kore-destructive" role="alert">{{ $errorMessage }}</p>
    @elseif($hint)
        <p class="mt-1 text-sm text-kore-muted-fg">{{ $hint }}</p>
    @endif
</div>
