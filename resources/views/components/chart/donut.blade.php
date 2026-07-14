@props([
    'y' => null,
    'label' => null,
    'inner' => 0.6,      // 0 = tarta
    'pad' => 1,          // separación entre porciones, en grados
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('donut')
        ->add(
            (new \KoreUi\Charts\Marks\DonutMark($y, $label))
                ->withRatio((float) $inner)
                ->withPad((float) $pad)
        );
@endphp
