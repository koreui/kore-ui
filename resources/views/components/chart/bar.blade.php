@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'stack' => null,     // barras con el mismo nombre de pila se acumulan
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('bar')
        ->add(new \KoreUi\Charts\Marks\BarMark($y, $label, $color, $stack));
@endphp
