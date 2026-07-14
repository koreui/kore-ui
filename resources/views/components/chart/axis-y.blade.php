@props([
    'ticks' => null,      // una PISTA, no un contrato: ver Ticks::ticks()
    'format' => null,     // number | currency | percent | compact
    'show' => true,       // los ejes salen por defecto: esta marca los CONFIGURA o los apaga
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('axis-y');
    $frame->axes['y'] = [
        'ticks' => $ticks !== null ? (int) $ticks : null,
        'format' => $format,
        'show' => filter_var($show, FILTER_VALIDATE_BOOL),
    ];
@endphp
