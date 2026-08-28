@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'mask' => null,
    // Los tres van a `null`, no a su valor: ver la nota de abajo.
    'emitFormatted' => null,
    'slotChar' => null,
    'autoClear' => null,
    'icon' => null,
    'iconRight' => null,
    'clearable' => false,
    'disabled' => false,
    'readonly' => false,
    'required' => false,
    'showError' => true,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');

    // Ojo con el valor por defecto: tiene que ser `null`. Con `false` —o con
    // `'_'`— el `?? config(...)` de abajo NO dispara nunca, porque el `??` solo
    // mira si es null, y entonces el ajuste de `config/kore-ui.php` no se aplica
    // jamás por mucho que la documentación lo prometa.
    $emitFormatted = $emitFormatted ?? config('kore-ui.form.maskable.emit_formatted', false);
    $slotChar = $slotChar ?? config('kore-ui.form.maskable.slot_char', '_');
    $autoClear = $autoClear ?? config('kore-ui.form.maskable.auto_clear', false);

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

    // Associate the field's hint/error with the control (WCAG 3.3.1 / 4.1.2).
    $describedBy = $hasError ? $fieldId . '-error' : ($hint ? $fieldId . '-hint' : null);

    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $iconSizeClasses = match($size) {
        'sm' => 'size-3.5',
        'lg' => 'size-5',
        default => 'size-4',
    };

    $iconPaddingLeft = $icon ? match($size) {
        'sm' => 'pl-8',
        'lg' => 'pl-11',
        default => 'pl-10',
    } : '';

    $hasRightIcon = $iconRight || $clearable;
    $iconPaddingRight = $hasRightIcon ? match($size) {
        'sm' => 'pr-8',
        'lg' => 'pr-11',
        default => 'pr-10',
    } : '';

    $maskJson = is_array($mask) ? json_encode($mask) : json_encode([$mask]);
    $firstMask = is_array($mask) ? $mask[0] : $mask;
    $autoPlaceholder = preg_replace('/[#A*!]/', $slotChar, $firstMask ?? '');
    $placeholder = $attributes->get('placeholder', $autoPlaceholder);

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $borderClasses = $hasError
        ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive'
        : 'border-kore-input';

    $inputBaseClasses = 'bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed';

    $jsConfig = json_encode(array_filter([
        'masks' => json_decode($maskJson),
        'emitFormatted' => $emitFormatted ?: null,
        'autoClear' => $autoClear ?: null,
    ], fn($v) => $v !== null));

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
        x-data="KoreMaskable({{ $jsConfig }})"
        {{ $atributosRaiz->merge(['class' => "relative"]) }}
    >
        @if($icon)
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <x-dynamic-component :component="'lucide-' . $icon" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
            </div>
        @endif

        @if(!$emitFormatted)
            {{-- Hidden input holds raw value for wire:model --}}
            <input
                type="hidden"
                x-ref="hiddenInput"
                {{ $wireModelAttr }}
                @if($name) name="{{ $name }}" @endif
            />
        @endif

        {{-- Visible input --}}
        <input
            type="text"
            x-ref="input"
            id="{{ $fieldId }}"
            @if($emitFormatted)
                {{ $wireModelAttr }}
                @if($name) name="{{ $name }}" @endif
            @endif
            placeholder="{{ $placeholder }}"
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $required ? 'required' : '' }}
            class="{{ $inputBaseClasses }} block w-full rounded-kore-md border {{ $borderClasses }} focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary {{ $sizeClasses }} {{ $iconPaddingLeft }} {{ $iconPaddingRight }}"
            x-on:input.prevent="_onInput($event)"
            x-on:paste="_onPaste($event)"
            x-on:blur="_onBlur()"
            x-on:focus="_onFocus($event)"
            autocomplete="off"
            @if($hasError) aria-invalid="true" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
        />

        @if($hasRightIcon)
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-1">
                @if($clearable)
                    <button
                        type="button"
                        x-show="formatted.length > 0"
                        x-cloak
                        x-on:click="clear(); $refs.input.focus()"
                        aria-label="{{ config('kore-ui.form.translations.clear', 'Limpiar') }}"
                        class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                    >
                        <x-lucide-x class="{{ $iconSizeClasses }}" />
                    </button>
                @endif
                @if($iconRight)
                    <x-dynamic-component :component="'lucide-' . $iconRight" class="{{ $iconSizeClasses }} text-kore-muted-fg" />
                @endif
            </div>
        @endif
    </div>
</x-kore::field>
