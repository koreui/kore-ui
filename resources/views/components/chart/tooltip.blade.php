@props([
    'crosshair' => true,
])
@php
    // Sin esta marca no se emite el payload. No es una optimización menor: el payload es una
    // SEGUNDA copia del dato en el DOM, y a 2.000 puntos pesa 53 kB — más que el propio <path>.
    app(\KoreUi\Charts\ChartContext::class)->current('tooltip')->tooltip = [
        'crosshair' => (bool) $crosshair,
    ];
@endphp
