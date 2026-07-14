@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'stack' => null,     // barras con el mismo nombre de pila se acumulan
    'show' => true,      // :show="false" la oculta PERO le reserva su color de la paleta
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('bar')
        ->add(
            (new \KoreUi\Charts\Marks\BarMark($y, $label, $color, $stack))
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
