@props([
    'max-labels' => null,   // cuántas etiquetas como mucho; el resto se saltan
    'hide' => false,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('axis-x');
    $frame->axes['x'] = [
        'max_labels' => $attributes->get('max-labels') !== null ? (int) $attributes->get('max-labels') : null,
        'hide' => (bool) $hide,
    ];
@endphp
