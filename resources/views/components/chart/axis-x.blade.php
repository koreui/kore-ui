@props([
    'max-labels' => null,   // cuántas etiquetas como mucho; el resto se saltan
    'show' => true,         // los ejes salen por defecto: esta marca los CONFIGURA o los apaga
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('axis-x');
    $frame->axes['x'] = [
        'max_labels' => $attributes->get('max-labels') !== null ? (int) $attributes->get('max-labels') : null,
        'show' => filter_var($show, FILTER_VALIDATE_BOOL),
    ];
@endphp
