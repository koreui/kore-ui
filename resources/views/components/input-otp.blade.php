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
    'readonly' => false,
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

    $fieldId = $attributes->get('id', \KoreUi\Core\Support\IdContext::para($name));

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

    // En solo lectura las casillas se leen y se recorren con el tabulador, pero
    // no aceptan escritura ni pegado. `readonly` en un input nativo ya bloquea
    // el tecleo; los manejadores se quitan igualmente porque `onKeydown` mueve
    // el foco y borra hacia atrás por su cuenta.
    $edicionBloqueada = $disabled || $readonly;

    // Los atributos que el consumidor escribe en la etiqueta y que ningún
    // @props declara —`data-*`, `class`, `style`, `aria-*`, `x-on:*`— no los
    // consumía nadie: se quedaban en el bag y no llegaban al DOM. No daba error,
    // simplemente no existían. Se vuelcan en la raíz del componente.
    // `id` se excluye porque ya lo usa $fieldId sobre el control, y `wire:model`
    // porque vive en el input oculto: duplicarlos daría dos ids iguales y dos
    // enlaces al mismo modelo.
    $atributosRaiz = $attributes->whereDoesntStartWith('wire:model')->except(['id']);
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
        @if($readonly) aria-readonly="true" @endif
        {{ $atributosRaiz->merge(['class' => "flex items-center gap-2"]) }}
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
        />

        @for($i = 0; $i < $length; $i++)
            @if($separatorAfter !== null && $i === (int) $separatorAfter)
                <span class="text-kore-muted-fg font-medium text-lg select-none">&ndash;</span>
            @endif

            <input
                type="{{ $masked ? 'password' : 'text' }}"
                x-ref="digit{{ $i }}"
                @if($i === 0) id="{{ $fieldId }}" @endif
                aria-label="{{ config('kore-ui.form.translations.digit', 'Dígito') }} {{ $i + 1 }}"
                maxlength="1"
                @if($numeric) inputmode="numeric" pattern="[0-9]*" @endif
                @if(! $edicionBloqueada)
                    x-on:input="onInput({{ $i }}, $event)"
                    x-on:keydown="onKeydown({{ $i }}, $event)"
                    x-on:paste="onPaste($event)"
                @endif
                x-on:focus="$event.target.select()"
                class="{{ $inputClasses }}"
                @if($disabled) disabled @endif
                @if($readonly) readonly @endif
                autocomplete="one-time-code"
            />
        @endfor
    </div>
</x-kore::field>
