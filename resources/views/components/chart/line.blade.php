@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'curve' => null,      // linear | monotone | step
    'dots' => false,
    'show' => true,       // :show="false" la oculta PERO le reserva su color de la paleta

    // Lo más lejos que pueden estar dos puntos consecutivos antes de que el trazo SE PARTA.
    // «30s», «5m», «2h»… o un número (las unidades del eje). Sólo tiene sentido en un eje
    // CONTINUO: en uno de categorías no hay huecos que medir.
    //
    // Sin esto, un sensor caído tres días se dibuja con una línea suave POR ENCIMA del hueco —
    // que es una curva inventada sobre un rato en el que no hubo dato.
    'maxGap' => null,
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
                ->withMaxGap($maxGap !== null ? \KoreUi\Charts\Duration::seconds($maxGap) : null)
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
