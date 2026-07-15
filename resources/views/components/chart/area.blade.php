@props([
    'y' => null,
    'label' => null,
    'color' => null,
    'curve' => null,
    'stack' => null,      // Las áreas con el mismo nombre se apilan (una banda sobre otra)
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
    app(\KoreUi\Charts\ChartContext::class)
        ->current('area')
        ->add(
            (new \KoreUi\Charts\Marks\AreaMark($y, $label, $color, $stack))
                ->withCurve($curve)
                ->withMaxGap($maxGap !== null ? \KoreUi\Charts\Duration::seconds($maxGap) : null)
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
