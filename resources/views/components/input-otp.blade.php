@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'length' => 6,
    'numeric' => false,
    'masked' => false,
    'separatorAfter' => null,
    'size' => null,
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

    $inputSize = match($size) {
        'sm' => 'size-8 text-sm',
        'lg' => 'size-12 text-lg',
        default => 'size-10 text-base',
    };

    $inputClasses = collect([
        $inputSize,
        'text-center rounded-kore-md border bg-kore-bg text-kore-fg font-medium',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
    ])->filter()->implode(' ');

    $wireModelAttr = $attributes->whereStartsWith('wire:model');
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
        x-data="KoreInputOtp({
            length: {{ $length }},
            numeric: {{ $numeric ? 'true' : 'false' }},
        })"
        class="flex items-center gap-2"
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
            id="{{ $fieldId }}"
        />

        @for($i = 0; $i < $length; $i++)
            @if($separatorAfter !== null && $i === (int) $separatorAfter)
                <span class="text-kore-muted-fg font-medium text-lg select-none">&ndash;</span>
            @endif

            <input
                type="{{ $masked ? 'password' : 'text' }}"
                x-ref="digit{{ $i }}"
                maxlength="1"
                @if($numeric) inputmode="numeric" pattern="[0-9]*" @endif
                x-on:input="onInput({{ $i }}, $event)"
                x-on:keydown="onKeydown({{ $i }}, $event)"
                x-on:paste="onPaste($event)"
                x-on:focus="$event.target.select()"
                class="{{ $inputClasses }}"
                @if($disabled) disabled @endif
                autocomplete="one-time-code"
            />
        @endfor
    </div>
</x-kore::field>
