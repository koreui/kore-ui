@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'curve' => null,      // linear | monotone | step
    'dots' => false,
])
@php
    // Una marca NO DIBUJA. Se apunta en el contexto y se calla.
    //
    // No puede dibujar aunque quisiera: el dominio del eje es la unión de todas las series,
    // así que nadie sabe dónde cae un punto hasta que la última marca se ha registrado. Como
    // Blade renderiza el slot ANTES que la plantilla del padre, cuando el gráfico se dibuja
    // ya nos tiene a todas.
    app(\KoreUi\Charts\ChartContext::class)
        ->current('line')
        ->add(
            (new \KoreUi\Charts\Marks\LineMark($y, $label, $color))
                ->withCurve($curve)
                ->withDots((bool) $dots)
        );
@endphp
