{{-- Anterior y siguiente, con palabras y sin números.

     Para tablas donde el número de página no significa nada para quien mira
     —una bandeja de entrada, un histórico— y para la paginación por cursor, que
     no tiene números que enseñar. --}}
@php
    $btn = 'inline-flex items-center rounded-kore-md px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring';
    $btnNormal = 'text-kore-fg bg-kore-muted/60 hover:bg-kore-muted';
    $btnDisabled = 'text-kore-muted-fg/40 cursor-not-allowed';
@endphp

@include('kore::datatable.paginators._nav', [
    'direccion' => 'prev',
    'accion' => $accionAnterior,
    'texto' => config('kore-ui.datatable.translations.previous', 'Anterior'),
    'clases' => $btn . ' ' . ($accionAnterior ? $btnNormal : $btnDisabled),
])

@include('kore::datatable.paginators._nav', [
    'direccion' => 'next',
    'accion' => $accionSiguiente,
    'texto' => config('kore-ui.datatable.translations.next', 'Siguiente'),
    'clases' => $btn . ' ' . ($accionSiguiente ? $btnNormal : $btnDisabled),
])
