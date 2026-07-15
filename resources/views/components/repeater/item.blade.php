@props([
    'fields' => [],
    'reorderable' => false,
    'deletable' => true,
])

@php
    $inputBase = 'w-full rounded-kore-md border border-kore-input bg-kore-bg text-kore-fg placeholder:text-kore-muted-fg focus:ring-2 focus:ring-kore-ring focus:border-kore-primary outline-none transition-colors text-sm py-1.5 px-2.5';

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
    @if($reorderable) x-sort:item="index" @endif
    class="flex items-start gap-2 rounded-kore-lg border border-kore-border bg-kore-surface p-3"
>
    @if($reorderable)
        <button
            type="button"
            x-sort:handle
            class="mt-2 shrink-0 cursor-grab text-kore-muted-fg hover:text-kore-fg transition-colors"
            aria-label="Arrastrar para reordenar"
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
                    <select x-model="row['{{ $key }}']" x-on:change="_sync()" class="{{ $inputBase }}">
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
                        class="{{ $inputBase }}"
                    />
                @endif
            </div>
        @endforeach
    </div>

    @if($deletable)
        <button
            type="button"
            x-on:click="removeRow(index)"
            class="mt-2 shrink-0 text-kore-muted-fg hover:text-kore-destructive transition-colors"
            aria-label="Eliminar fila"
        >
            <x-lucide-x class="size-4" />
        </button>
    @endif
</div>
