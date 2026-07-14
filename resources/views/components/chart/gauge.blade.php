@props([
    'y' => null,          // la columna del valor (se usa la primera fila con dato)
    'label' => null,      // para la etiqueta accesible
    'show' => true,

    'min' => 0,
    'max' => 100,

    // Cuántos grados abarca el arco. 270 = velocímetro (hueco abajo); 180 = semicírculo.
    'sweep' => 270,

    // Los rangos de color, como [cota => token]: [60 => 'success', 85 => 'warning', 100 => 'destructive'].
    // ⚠️ SIN ellos, un gauge es un stat tile con un anillo decorativo. Ver docs/chart/gauge.md.
    'thresholds' => [],

    // El texto bajo el número: «CPU», «SLA»…
    'caption' => null,
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('gauge')
        ->add(
            (new \KoreUi\Charts\Marks\GaugeMark($y, $label))
                ->withRange((float) $min, (float) $max)
                ->withSweep((float) $sweep)
                ->withThresholds($thresholds)
                ->withCaption($caption)
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
