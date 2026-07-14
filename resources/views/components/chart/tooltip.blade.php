@props([
    'crosshair' => true,
    'show' => true,
])
@php
    // Sin esta marca no se emite el payload. No es una optimización menor: el payload es una
    // SEGUNDA copia del dato en el DOM, y a 2.000 puntos pesa 53 kB — más que el propio <path>.
    //
    // Por eso `:show="false"` aquí NO es cosmético: no esconde el tooltip con CSS, es que no
    // se emite el payload. El gráfico adelgaza de verdad.
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('tooltip');

    if (filter_var($show, FILTER_VALIDATE_BOOL)) {
        $frame->tooltip = [
            'crosshair' => filter_var($crosshair, FILTER_VALIDATE_BOOL),
        ];
    }
@endphp
