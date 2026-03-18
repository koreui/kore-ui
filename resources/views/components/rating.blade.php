@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'stars' => 5,
    'allowHalf' => false,
    'readonly' => false,
    'clearable' => true,
    'disabled' => false,
    'required' => false,
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

    $starSize = match($size) {
        'sm' => 'size-4',
        'lg' => 'size-7',
        default => 'size-5',
    };

    $gapClass = match($size) {
        'sm' => 'gap-0.5',
        'lg' => 'gap-1.5',
        default => 'gap-1',
    };

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = json_encode(array_filter([
        'stars' => (int) $stars,
        'allowHalf' => $allowHalf ?: null,
        'readonly' => ($readonly || $disabled) ?: null,
        'clearable' => $clearable ? null : false,
    ], fn($v) => $v !== null));
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
        x-data="KoreRating({{ $jsConfig }})"
        @if($readonly)
            role="img"
            aria-label="Rating"
        @else
            role="radiogroup"
            aria-label="{{ $label ?? 'Rating' }}"
        @endif
        class="inline-flex items-center {{ $gapClass }} {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
        @if(!$readonly && !$disabled)
            x-on:mouseleave="clearPreview()"
        @endif
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            id="{{ $fieldId }}"
        />

        @for($i = 1; $i <= $stars; $i++)
            <button
                type="button"
                class="relative cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-kore-ring rounded-sm {{ $disabled || $readonly ? 'pointer-events-none' : '' }}"
                @if(!$readonly && !$disabled)
                    x-on:click="rate({{ $i }})"
                    x-on:mouseenter="preview({{ $i }})"
                    @if($allowHalf)
                        x-on:mousemove="detectHalf($event, {{ $i }})"
                    @endif
                    role="radio"
                    :aria-checked="displayValue >= {{ $i }} ? 'true' : 'false'"
                    aria-label="{{ $i }} de {{ $stars }} estrellas"
                @endif
            >
                {{-- Background star (empty) --}}
                <x-lucide-star class="{{ $starSize }} text-kore-muted-fg/30" />

                {{-- Foreground star (full) --}}
                <x-lucide-star
                    class="{{ $starSize }} absolute inset-0 text-kore-warning fill-kore-warning transition-opacity duration-100"
                    x-show="getStarFill({{ $i }}) === 'full'"
                    x-cloak
                />

                {{-- Foreground star (half) --}}
                @if($allowHalf)
                    <x-lucide-star
                        class="{{ $starSize }} absolute inset-0 text-kore-warning fill-kore-warning transition-opacity duration-100"
                        style="clip-path: inset(0 50% 0 0)"
                        x-show="getStarFill({{ $i }}) === 'half'"
                        x-cloak
                    />
                @endif
            </button>
        @endfor
    </div>
</x-kore::field>
