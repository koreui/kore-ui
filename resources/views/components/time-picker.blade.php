@props([
    'label' => null,
    'hint' => null,
    'name' => null,
    'error' => null,
    'size' => null,
    'placeholder' => null,
    'clearable' => false,
    'disabled' => false,
    'required' => false,
    'showError' => true,

    // Time
    'timeFormat' => '24',
    'minuteStep' => 1,
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

    $baseClasses = collect([
        'w-full rounded-kore-md border bg-kore-bg text-kore-fg',
        'transition-colors duration-150',
        'focus:outline-none focus:ring-2 focus:ring-kore-ring focus:border-kore-primary',
        'disabled:opacity-50 disabled:cursor-not-allowed',
        $hasError ? 'border-kore-destructive focus:ring-kore-destructive/30 focus:border-kore-destructive' : 'border-kore-input',
        $sizeClasses,
    ])->filter()->implode(' ');

    $wireModelAttr = $attributes->whereStartsWith('wire:model');

    $jsConfig = json_encode(array_filter([
        'timeFormat' => $timeFormat,
        'minuteStep' => $minuteStep > 1 ? $minuteStep : null,
    ], fn ($v) => $v !== null));

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
        x-data="KoreTimePicker({{ $jsConfig }})"
        x-on:keydown="onKeydown($event)"
        wire:ignore
        {{ $atributosRaiz->merge(['class' => "relative"]) }}
    >
        {{-- Hidden input for wire:model --}}
        <input
            type="hidden"
            x-ref="hiddenInput"
            {{ $wireModelAttr }}
            @if($name) name="{{ $name }}" @endif
        />

        {{-- Trigger --}}
        <button
            type="button"
            x-ref="trigger"
            x-on:click="toggle()"
            id="{{ $fieldId }}"
            class="{{ $baseClasses }} flex items-center justify-between gap-2 text-left cursor-pointer {{ $disabled ? 'opacity-50 cursor-not-allowed' : '' }}"
            @if($disabled) disabled @endif
            role="combobox"
            aria-haspopup="dialog"
            x-bind:aria-expanded="open"
        >
            <div class="flex-1 truncate min-w-0">
                <span x-show="hasValue" x-text="displayValue" class="block truncate"></span>
                <span x-show="!hasValue" class="text-kore-muted-fg">{!! $placeholder ? e($placeholder) : '&nbsp;' !!}</span>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                @if($clearable)
                    <template x-if="hasValue">
                        <button
                            type="button"
                            x-on:click.stop="clear()"
                            class="text-kore-muted-fg hover:text-kore-fg transition-colors"
                        >
                            <x-lucide-x class="{{ $iconSizeClasses }}" />
                        </button>
                    </template>
                @endif
                <x-lucide-clock class="{{ $iconSizeClasses }} text-kore-muted-fg" />
            </div>
        </button>

        {{-- Time panel --}}
        <template x-teleport="body">
            <div
                data-kore-teleport
                x-ref="dropdown"
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                {{-- El panel escucha el teclado él mismo: teleportado a
                     `<body>`, los eventos de dentro no burbujean por la raíz del
                     componente, y aquí hay botones tabulables. --}}
                x-on:keydown="onKeydown($event)"
                x-on:mousedown.stop
                class="fixed z-[9999] rounded-kore-lg border border-kore-border bg-kore-bg text-kore-fg shadow-lg p-4"
                role="dialog"
                aria-label="Choose time"
            >
                <div class="flex items-center justify-center gap-2">
                    {{-- Hours --}}
                    <div class="flex flex-col items-center">
                        <button
                            type="button"
                            x-on:mousedown="startHold(() => incrementHour())"
                            x-on:mouseup="stopHold()"
                            x-on:mouseleave="stopHold()"
                            x-on:touchstart.prevent="startHold(() => incrementHour())"
                            x-on:touchend="stopHold()"
                            aria-label="{{ config('kore-ui.form.translations.increment_hour', 'Subir la hora') }}"
                            class="p-1 text-kore-muted-fg hover:text-kore-fg hover:bg-kore-muted rounded-kore-sm transition-colors"
                        >
                            <x-lucide-chevron-up class="size-5" />
                        </button>
                        <span
                            x-text="String(hours).padStart(2, '0')"
                            class="text-2xl font-medium text-kore-fg tabular-nums w-10 text-center py-1"
                        ></span>
                        <button
                            type="button"
                            x-on:mousedown="startHold(() => decrementHour())"
                            x-on:mouseup="stopHold()"
                            x-on:mouseleave="stopHold()"
                            x-on:touchstart.prevent="startHold(() => decrementHour())"
                            x-on:touchend="stopHold()"
                            aria-label="{{ config('kore-ui.form.translations.decrement_hour', 'Bajar la hora') }}"
                            class="p-1 text-kore-muted-fg hover:text-kore-fg hover:bg-kore-muted rounded-kore-sm transition-colors"
                        >
                            <x-lucide-chevron-down class="size-5" />
                        </button>
                    </div>

                    <span class="text-2xl font-medium text-kore-fg">:</span>

                    {{-- Minutes --}}
                    <div class="flex flex-col items-center">
                        <button
                            type="button"
                            x-on:mousedown="startHold(() => incrementMinute())"
                            x-on:mouseup="stopHold()"
                            x-on:mouseleave="stopHold()"
                            x-on:touchstart.prevent="startHold(() => incrementMinute())"
                            x-on:touchend="stopHold()"
                            aria-label="{{ config('kore-ui.form.translations.increment_minute', 'Subir los minutos') }}"
                            class="p-1 text-kore-muted-fg hover:text-kore-fg hover:bg-kore-muted rounded-kore-sm transition-colors"
                        >
                            <x-lucide-chevron-up class="size-5" />
                        </button>
                        <span
                            x-text="String(minutes).padStart(2, '0')"
                            class="text-2xl font-medium text-kore-fg tabular-nums w-10 text-center py-1"
                        ></span>
                        <button
                            type="button"
                            x-on:mousedown="startHold(() => decrementMinute())"
                            x-on:mouseup="stopHold()"
                            x-on:mouseleave="stopHold()"
                            x-on:touchstart.prevent="startHold(() => decrementMinute())"
                            x-on:touchend="stopHold()"
                            aria-label="{{ config('kore-ui.form.translations.decrement_minute', 'Bajar los minutos') }}"
                            class="p-1 text-kore-muted-fg hover:text-kore-fg hover:bg-kore-muted rounded-kore-sm transition-colors"
                        >
                            <x-lucide-chevron-down class="size-5" />
                        </button>
                    </div>

                    {{-- AM/PM toggle --}}
                    @if($timeFormat === '12')
                        <div class="flex flex-col items-center ml-2">
                            <button
                                type="button"
                                x-on:click="toggleAmPm()"
                                x-text="ampm"
                                class="px-3 py-2 text-sm font-medium rounded-kore-md border border-kore-input bg-kore-bg text-kore-fg hover:bg-kore-muted transition-colors"
                            ></button>
                        </div>
                    @endif
                </div>
            </div>
        </template>
    </div>
</x-kore::field>
