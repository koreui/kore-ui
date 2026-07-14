@props([
    'position' => 'top',   // top | bottom
    'show' => true,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('legend');

    if (filter_var($show, FILTER_VALIDATE_BOOL)) {
        $frame->legend = [
            'position' => in_array($position, ['top', 'bottom'], true) ? $position : 'top',
        ];
    }
@endphp
