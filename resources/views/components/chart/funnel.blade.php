@props([
    'y' => null,          // la columna del valor de cada etapa
    'label' => null,
    'show' => true,
])
@php
    // El orden de las filas ES el orden del embudo: la primera etapa arriba. No se reordena.
    app(\KoreUi\Charts\ChartContext::class)
        ->current('funnel')
        ->add(
            (new \KoreUi\Charts\Marks\FunnelMark($y, $label))
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
