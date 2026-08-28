@props([
    'label' => null,
    'hint' => null,
    'hasError' => false,
    'errorMessage' => null,
    'fieldId' => null,
    'required' => false,

    // `<label for>` solo vale contra un control de formulario. Cuando lo que
    // envuelve el field es un contenedor —un role="radiogroup", un calendario
    // empotrado— el `for` apunta a algo que no es etiquetable: la etiqueta se
    // queda huérfana y el grupo, sin nombre. En ese caso el consumidor pone
    // `labelable` a false y nombra el contenedor con aria-labelledby.
    'labelable' => true,
])

<div {{ $attributes->only('class')->merge(['class' => 'kore-field']) }}>
    @if($label)
        <label
            @if($fieldId) id="{{ $fieldId }}-label" @endif
            @if($fieldId && $labelable) for="{{ $fieldId }}" @endif
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
        <p @if($fieldId) id="{{ $fieldId }}-error" @endif class="mt-1 text-sm text-kore-destructive" role="alert">{{ $errorMessage }}</p>
    @elseif($hint)
        <p @if($fieldId) id="{{ $fieldId }}-hint" @endif class="mt-1 text-sm text-kore-muted-fg">{{ $hint }}</p>
    @endif
</div>
