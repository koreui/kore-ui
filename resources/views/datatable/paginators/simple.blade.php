{{-- Anterior y siguiente, con palabras y sin números.

     Para tablas donde el número de página no significa nada para quien mira
     —una bandeja de entrada, un histórico— y para la paginación por cursor, que
     no tiene números que enseñar. --}}
@php
    $btn = 'kore-tool';
    $btnNormal = '';
    $btnDisabled = 'opacity-50 pointer-events-none';
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
