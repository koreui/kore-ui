@props([
    'position' => 'top',   // top | bottom
])
@php
    app(\KoreUi\Charts\ChartContext::class)->current('legend')->legend = [
        'position' => in_array($position, ['top', 'bottom'], true) ? $position : 'top',
    ];
@endphp
