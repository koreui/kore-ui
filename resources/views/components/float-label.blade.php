@props([
    'label' => null,
    'variant' => 'over',
])

@php
    // Resting position (no focus, no content)
    $restClasses = match($variant) {
        // In: label inside input, near top, normal size
        'in' => 'top-2.5 left-3 text-sm',
        // On: always on the border
        'on' => '-top-2.5 left-2 text-xs bg-kore-bg px-1',
        // Over: label centered like a placeholder
        default => 'top-1/2 left-3 -translate-y-1/2 text-sm',
    };

    // Active position (focus or has content)
    $activeClasses = match($variant) {
        // In: shrinks but stays inside the input
        'in' => '!top-1 !text-[10px] text-kore-primary',
        // On: just changes color
        'on' => 'text-kore-primary',
        // Over: floats up to the border with bg cutout (like "on" but animated)
        default => '!-top-2.5 !-translate-y-0 !text-xs !bg-kore-bg !px-1 text-kore-primary',
    };

    // Only "in" needs extra padding (label stays inside, text goes below)
    // "over" doesn't need it (label leaves the input area to the border)
    $inputPadding = match($variant) {
        'in' => '[&_input]:pt-5 [&_input]:pb-1 [&_textarea]:pt-5 [&_textarea]:pb-1',
        default => '',
    };
@endphp

<div
    x-data="{ focused: false, filled: false }"
    x-on:focusin="focused = true"
    x-on:focusout="focused = false; filled = $event.target.value?.length > 0"
    x-on:input="filled = $event.target.value?.length > 0"
    x-init="$nextTick(() => {
        // El control lo pone quien usa el componente, así que el id no se puede
        // escribir en el Blade: se busca en tiempo de ejecución. Sin este enlace
        // la etiqueta no nombra a nada —no tenía ni `for` ni envolvía al input—
        // y el campo se anunciaba sin nombre, que es justo lo contrario de lo
        // que hace un float-label.
        let input = $el.querySelector('input:not([type=hidden]), textarea, select');
        if (! input) return;
        if (! input.id) input.id = 'kore-float-' + Math.random().toString(36).slice(2, 9);
        if ($refs.etiqueta && ! $refs.etiqueta.htmlFor) $refs.etiqueta.htmlFor = input.id;
        if (input.value) filled = true;
    })"
    class="kore-float-label relative {{ $inputPadding }}"
>
    <div class="relative">
        {{ $slot }}

        @if($label)
            <label
                x-ref="etiqueta"
                class="absolute pointer-events-none text-kore-muted-fg transition-all duration-200 origin-top-left {{ $restClasses }}"
                x-bind:class="(focused || filled) && '{{ $activeClasses }}'"
            >
                {{ $label }}
            </label>
        @endif
    </div>
</div>
