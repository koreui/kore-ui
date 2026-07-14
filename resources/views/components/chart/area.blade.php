@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'curve' => null,
    'show' => true,       // :show="false" la oculta PERO le reserva su color de la paleta
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('area')
        ->add(
            (new \KoreUi\Charts\Marks\AreaMark($y, $label, $color))
                ->withCurve($curve)
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
