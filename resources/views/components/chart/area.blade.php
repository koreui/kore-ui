@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'curve' => null,
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('area')
        ->add(
            (new \KoreUi\Charts\Marks\AreaMark($y, $label, $color))->withCurve($curve)
        );
@endphp
