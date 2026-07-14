@props([
    'max-labels' => null,   // TOPE de etiquetas en una escala de bandas; el resto se saltan
    'show' => true,         // los ejes salen por defecto: esta marca los CONFIGURA o los apaga

    // Cuántos ticks buscar en una escala CONTINUA (temporal o lineal). Es una pista, no un
    // contrato — igual que en el eje Y. Por defecto, 6: pedir doce para una semana da un tick
    // cada doce horas y se pisan unos con otros.
    'ticks' => null,

    // De qué está hecho el eje X:
    //
    //   auto    (por defecto) — hay fechas → `time`; cualquier otra cosa → `band`
    //   band    categorías. La fila N cae en la banda N
    //   time    fechas. Cada punto cae DONDE LE TOCA en el calendario, y los huecos se ven
    //   linear  números. Lo que necesita un scatter
    //
    // `auto` NUNCA promociona a `linear` por su cuenta: unos años escritos como enteros
    // (2022, 2023, 2024) son categorías, no una recta numérica.
    'scale' => 'auto',

    // La zona con la que se lee la fecha. Por defecto, la de la aplicación — que es donde vive
    // el usuario, no donde vive la base de datos.
    'timezone' => null,
])
@php
    $frame = app(\KoreUi\Charts\ChartContext::class)->current('axis-x');
    $frame->axes['x'] = [
        'max_labels' => $attributes->get('max-labels') !== null ? (int) $attributes->get('max-labels') : null,
        'show' => filter_var($show, FILTER_VALIDATE_BOOL),
        'ticks' => $ticks !== null ? (int) $ticks : null,
        'scale' => $scale,
        'timezone' => $timezone,
    ];
@endphp
