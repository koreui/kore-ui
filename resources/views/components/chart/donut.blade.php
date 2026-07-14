@props([
    'y' => null,
    'label' => null,
    'inner' => 0.6,        // 0 = tarta
    'pad' => 1,            // separación entre porciones, en grados
    'highlight' => null,   // enciende el arco Y su fila de la leyenda a la vez (CSS puro)
    'show' => true,
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('donut')
        ->add(
            (new \KoreUi\Charts\Marks\DonutMark($y, $label))
                ->withRatio((float) $inner)
                ->withPad((float) $pad)
                ->withHighlight(
                    $highlight !== null
                        ? filter_var($highlight, FILTER_VALIDATE_BOOL)
                        : (bool) config('kore-ui.chart.donut_highlight', true)
                )
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
