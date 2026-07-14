@props([
    'ticks' => null,      // una PISTA, no un contrato: ver Ticks::ticks()
    'format' => null,     // number | currency | percent | compact
    'show' => true,       // los ejes salen por defecto: esta marca los CONFIGURA o los apaga

    // Fijar el dominio, en vez de deducirlo del dato.
    //
    // Es un CONTRATO, no una sugerencia: no se redondea por debajo de lo que pides. Y en un
    // gráfico EN VIVO deja de ser un lujo: un eje que se reescala cada dos segundos porque el
    // dato subió un punto es ilegible — la línea se queda quieta y lo que se mueve es el eje.
    // Un porcentaje va de 0 a 100 y punto.
    'min' => null,
    'max' => null,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('axis-y');
    $frame->axes['y'] = [
        'ticks' => $ticks !== null ? (int) $ticks : null,
        'format' => $format,
        'show' => filter_var($show, FILTER_VALIDATE_BOOL),
        'min' => $min !== null ? (float) $min : null,
        'max' => $max !== null ? (float) $max : null,
    ];
@endphp
