@props([
    'y' => null,          // la columna del valor de cada etapa
    'label' => null,
    'show' => true,

    // Al posarse sobre una etapa, se enciende el par (trapecio + fila) y se apaga el resto. Es CSS
    // puro; :highlight="false" lo apaga entero (en un embudo de dos etapas sobra). Por defecto,
    // lo que diga la config, y si no, sí.
    'highlight' => null,
])
@php
    // El orden de las filas ES el orden del embudo: la primera etapa arriba. No se reordena.
    app(\KoreUi\Charts\ChartContext::class)
        ->current('funnel')
        ->add(
            (new \KoreUi\Charts\Marks\FunnelMark($y, $label))
                ->withHighlight(filter_var(
                    $highlight ?? config('kore-ui.chart.funnel_highlight', true),
                    FILTER_VALIDATE_BOOL,
                ))
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
