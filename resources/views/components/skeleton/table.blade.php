{{-- La silueta de una <x-kore::table>: la cabecera y unas cuantas filas.

     Se dibuja con una tabla de verdad, no con divs, para que las columnas caigan
     donde caerán después y la fila no cambie de alto al llegar los datos. --}}
@props([
    'columns' => 4,
    'rows' => 5,
    'headerless' => false,
    'density' => null,
    'responsive' => true,
    'shadow' => false,
])

@php
    $columns = max(1, (int) $columns);
    $rows = max(1, (int) $rows);
    $density = $density ?? config('kore-ui.datatable.density', 'normal');

    $celda = match($density) {
        'compact' => 'px-3 py-1.5',
        'comfortable' => 'px-4 py-4',
        default => 'px-4 py-3',
    };

    // Anchos desiguales: una tabla de barras todas iguales no se parece a
    // ninguna tabla real, y el ojo lo nota.
    $anchos = ['80%', '60%', '70%', '45%', '85%', '55%'];
@endphp

<div
    {{ $attributes->class([
        'rounded-kore-lg border border-kore-border bg-kore-surface',
        'shadow-sm' => $shadow,
        'overflow-x-auto' => $responsive,
    ]) }}
    role="status"
    aria-busy="true"
>
    <span class="sr-only">{{ config('kore-ui.ui.translations.loading', 'Cargando') }}</span>

    <table class="min-w-full divide-y divide-kore-border">
        @unless($headerless)
            <thead class="bg-kore-muted/50">
                <tr>
                    @for($c = 0; $c < $columns; $c++)
                        <th class="{{ $celda }} text-left">
                            <x-kore::skeleton shape="text" height="0.75rem" :width="$anchos[$c % count($anchos)]" />
                        </th>
                    @endfor
                </tr>
            </thead>
        @endunless

        <tbody class="divide-y divide-kore-border">
            @for($r = 0; $r < $rows; $r++)
                <tr>
                    @for($c = 0; $c < $columns; $c++)
                        <td class="{{ $celda }}">
                            <x-kore::skeleton shape="text" height="0.875rem" :width="$anchos[($r + $c) % count($anchos)]" />
                        </td>
                    @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
