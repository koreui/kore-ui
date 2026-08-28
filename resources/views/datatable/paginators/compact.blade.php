{{-- Un solo control: las dos flechas y, entre ellas, en qué página se está.

     Ocupa lo mismo con veinte páginas que con dos mil, así que es el que cabe
     en una tabla estrecha o dentro de un modal. El indicador va con
     `tabular-nums`: sin eso, el control cambia de ancho al pasar de la página 9
     a la 10 y las flechas se mueven bajo el cursor. --}}
@php
    $carril = 'inline-flex items-center gap-0.5 rounded-kore-md border border-kore-border bg-kore-surface p-0.5';
    $btn = 'inline-flex items-center justify-center size-7 rounded-kore-sm transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring';
    $btnNormal = 'text-kore-fg hover:bg-kore-muted';
    $btnDisabled = 'text-kore-muted-fg/40 cursor-not-allowed';
@endphp

<span class="{{ $carril }}">
    @include('kore::datatable.paginators._nav', [
        'direccion' => 'prev',
        'accion' => $accionAnterior,
        'clases' => $btn . ' ' . ($accionAnterior ? $btnNormal : $btnDisabled),
    ])

    @unless($isCursor)
        <span class="select-none px-2 text-sm tabular-nums text-kore-muted-fg">
            <span class="font-semibold text-kore-fg">{{ $currentPage }}</span>
            @if($lastPage) / {{ $lastPage }} @endif
        </span>
    @endunless

    @include('kore::datatable.paginators._nav', [
        'direccion' => 'next',
        'accion' => $accionSiguiente,
        'clases' => $btn . ' ' . ($accionSiguiente ? $btnNormal : $btnDisabled),
    ])
</span>
