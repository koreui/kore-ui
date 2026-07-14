@props([
    'y' => null,          // la columna del VALOR (el color de la celda)
    'row' => null,        // la columna de la FILA (la columna la da el `x` del gráfico)
    'buckets' => 5,       // cuántos escalones de color (3–7)
    'show' => true,
])
@php
    app(\KoreUi\Charts\ChartContext::class)
        ->current('heatmap')
        ->add(
            (new \KoreUi\Charts\Marks\HeatmapMark($y))
                ->withRow($row)
                ->withBuckets((int) $buckets)
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
