@props([
    'ticks' => null,      // una PISTA, no un contrato: ver Ticks::ticks()
    'format' => null,     // number | currency | percent | compact
    'hide' => false,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('axis-y');
    $frame->axes['y'] = [
        'ticks' => $ticks !== null ? (int) $ticks : null,
        'format' => $format,
        'hide' => (bool) $hide,
    ];
@endphp
