@props([
    'fields' => [],
    'reorderable' => false,
    'deletable' => true,
    'readonly' => false,
])

{{-- El tamaño lo pone el <x-kore::repeater> que envuelve. Lo declaraba y lo
     resolvía contra la configuración, pero no se lo pasaba a nadie: los campos
     de las filas llevaban su tamaño escrito a mano, así que `size` no hacía
     nada. --}}
@aware([
    'size' => null,
])

@php
    $size = $size ?? config('kore-ui.form.size', 'md');

    // Las mismas medidas que <x-kore::input>, para que una fila del repeater no
    // desentone al lado de un campo suelto.
    $sizeClasses = match($size) {
        'sm' => 'text-xs py-1.5 px-2.5',
        'lg' => 'text-base py-2.5 px-3.5',
        default => 'text-sm py-2 px-3',
    };

    $inputBase = 'w-full rounded-kore-md border border-kore-input bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg focus:ring-2 focus:ring-kore-ring focus:border-kore-primary outline-none transition-colors ' . $sizeClasses;

    // Literal grid classes so Tailwind v4 picks them up from the source (no runtime interpolation).
    $gridCols = match(count($fields)) {
        1 => 'sm:grid-cols-1',
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-3',
        4 => 'sm:grid-cols-4',
        default => 'sm:grid-cols-2',
    };
@endphp

<div
    @if($reorderable && ! $readonly) x-sort:item="index" @endif
    class="flex items-start gap-2 rounded-kore-lg border border-kore-border bg-kore-surface p-3"
>
    @if($reorderable && ! $readonly)
        <button
            type="button"
            x-sort:handle
            class="mt-2 shrink-0 cursor-grab text-kore-muted-fg hover:text-kore-fg transition-colors"
            aria-label="{{ config('kore-ui.ui.translations.reorder', 'Arrastrar para reordenar') }}"
        >
            <x-lucide-grip-vertical class="size-4" />
        </button>
    @endif

    <div class="flex-1 grid grid-cols-1 {{ $gridCols }} gap-2">
        @foreach($fields as $field)
            @php
                $key = $field['key'];
                $type = $field['type'] ?? 'text';
                $placeholder = $field['placeholder'] ?? ($field['label'] ?? $key);
            @endphp

            <div>
                @isset($field['label'])
                    <label class="mb-1 block text-xs font-medium text-kore-muted-fg">{{ $field['label'] }}</label>
                @endisset

                @if($type === 'select')
                    <select
                        x-model="row['{{ $key }}']"
                        x-on:change="_sync()"
                        @if($readonly)
                            aria-readonly="true"
                            x-on:mousedown.prevent
                            x-on:keydown="if ($event.key !== 'Tab') $event.preventDefault()"
                        @endif
                        class="{{ $inputBase }}"
                    >
                        <option value="">—</option>
                        @foreach(($field['options'] ?? []) as $value => $optLabel)
                            <option value="{{ $value }}">{{ $optLabel }}</option>
                        @endforeach
                    </select>
                @else
                    <input
                        type="{{ $type }}"
                        x-model="row['{{ $key }}']"
                        x-on:change="_sync()"
                        placeholder="{{ $placeholder }}"
                        @if($readonly) readonly @endif
                        class="{{ $inputBase }}"
                    />
                @endif
            </div>
        @endforeach
    </div>

    @if($deletable && ! $readonly)
        <button
            type="button"
            x-on:click="removeRow(index)"
            class="mt-2 shrink-0 text-kore-muted-fg hover:text-kore-destructive transition-colors"
            aria-label="{{ config('kore-ui.ui.translations.remove_row', 'Eliminar fila') }}"
        >
            <x-lucide-x class="size-4" />
        </button>
    @endif
</div>
