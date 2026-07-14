@props([
    // Cada cuánto se pide el dato nuevo: «5s», «500ms», o un número de milisegundos.
    'every' => '5s',

    // El método del componente Livewire que trae el dato nuevo — como `wire:poll.5s="tick"`.
    // Sin él, se hace un `$refresh()` a secas: sirve cuando el dato lo produce el propio render
    // (una consulta), no cuando hay que avanzar un estado.
    'call' => null,

    // Las marcas se mueven a su sitio nuevo en vez de saltar. Sólo aplica a barras y puntos —
    // ver el aviso de KoreUi\Charts\Stream sobre por qué la línea NO se anima.
    'transition' => true,

    'show' => true,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('stream');

    if (filter_var($show, FILTER_VALIDATE_BOOL)) {
        $frame->stream = [
            'every' => \KoreUi\Charts\Stream::interval($every),
            'call' => $call,
            'transition' => filter_var($transition, FILTER_VALIDATE_BOOL),
        ];
    }
@endphp
