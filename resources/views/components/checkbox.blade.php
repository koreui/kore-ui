@props([
    'label' => null,
    'description' => null,
    'size' => null,
    'labelPosition' => 'right',
    'indeterminate' => false,
    'disabled' => false,
    'name' => null,
    'error' => null,
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

    // Associate the error/description with the control (WCAG 3.3.1 / 4.1.2).
    $describedBy = $hasError ? $fieldId . '-error' : ($description ? $fieldId . '-description' : null);

    $checkboxSize = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $labelSize = match($size) {
        'sm' => 'text-xs',
        'lg' => 'text-base',
        default => 'text-sm',
    };

    $checkboxClasses = collect([
        $checkboxSize,
        'shrink-0 mt-0.5',
        'rounded-kore-sm border appearance-none cursor-pointer',
        'transition-colors duration-150',
        'checked:bg-kore-primary checked:border-kore-primary',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:ring-offset-2 ring-offset-kore-bg',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive' : 'border-kore-input',
        // Sin comillas dentro de url(), y los espacios como %20.
        //
        // Tailwind v4 extrae las clases del texto del archivo partiendo por
        // espacios en blanco, así que un valor arbitrario que contenga espacios
        // —y un SVG en línea los tiene: `viewBox='0 0 16 16'`— se corta en el
        // primero y la utilidad no llega a generarse. Las comillas escapadas
        // (\") del original tenían el mismo efecto: la barra invertida entra en
        // el candidato y lo invalida. Ni error de compilación ni nada en el CSS
        // — la casilla marcada se quedaba en un cuadrado de color liso, sin
        // palomita, y llevaba así desde que se escribió el componente. Hay un
        // cepo en tests/Ui/ClasesArbitrariasTest.php para el próximo.
        'checked:bg-[url(data:image/svg+xml,%3csvg%20viewBox=%270%200%2016%2016%27%20fill=%27white%27%20xmlns=%27http://www.w3.org/2000/svg%27%3e%3cpath%20d=%27M12.207%204.793a1%201%200%20010%201.414l-5%205a1%201%200%2001-1.414%200l-2-2a1%201%200%20011.414-1.414L6.5%209.086l4.293-4.293a1%201%200%20011.414%200z%27/%3e%3c/svg%3e)] checked:bg-center checked:bg-no-repeat',
        // El estado indeterminado tampoco se veía: `appearance-none` quita el
        // guion que pinta el navegador y solo había estilos para `checked`, así
        // que una casilla en «mixto» era indistinguible de una sin marcar. La
        // propiedad sí estaba puesta —y por eso el árbol de accesibilidad la
        // anunciaba como «mixed»— pero a la vista no había nada.
        'indeterminate:bg-kore-primary indeterminate:border-kore-primary',
        'indeterminate:bg-[url(data:image/svg+xml,%3csvg%20viewBox=%270%200%2016%2016%27%20fill=%27white%27%20xmlns=%27http://www.w3.org/2000/svg%27%3e%3cpath%20d=%27M4%207h8v2H4z%27/%3e%3c/svg%3e)] indeterminate:bg-center indeterminate:bg-no-repeat',

    ])->filter()->implode(' ');
@endphp

<div
    class="kore-checkbox"
    @if($indeterminate)
        x-data
        x-init="$el.querySelector('input[type=checkbox]').indeterminate = true"
    @endif
>
    <div class="flex items-start gap-2 {{ $labelPosition === 'left' ? 'flex-row-reverse justify-end' : '' }}">
        <input
            type="checkbox"
            {{ $attributes->merge([
                'id' => $fieldId,
                'name' => $name,
                'disabled' => $disabled,
                'aria-invalid' => $hasError ? 'true' : null,
                'aria-describedby' => $describedBy,
                'class' => $checkboxClasses,
            ])->except(['label', 'description', 'size', 'label-position', 'indeterminate', 'error', 'show-error']) }}
        />

        @if($label || $description)
            <div class="select-none">
                @if($label)
                    <label for="{{ $fieldId }}" class="{{ $labelSize }} font-medium text-kore-fg cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}">
                        {{ $label }}
                    </label>
                @endif
                @if($description)
                    <p @if($fieldId) id="{{ $fieldId }}-description" @endif class="text-xs text-kore-muted-fg mt-0.5 {{ $disabled ? 'opacity-50' : '' }}">{{ $description }}</p>
                @endif
            </div>
        @endif
    </div>

    @if($hasError && $errorMessage)
        <p @if($fieldId) id="{{ $fieldId }}-error" @endif class="mt-1 text-sm text-kore-destructive" role="alert">{{ $errorMessage }}</p>
    @endif
</div>
