@props([
    'y' => null,          // la columna del SALTO de cada etapa (positivo sube, negativo baja)
    'label' => null,
    'show' => true,

    // La columna booleana que marca los TOTALES: filas que van del cero al acumulado y no mueven
    // la suma. Un descansillo, no un salto. Sin ella, la cascada es puramente relativa.
    'total' => null,

    // Las líneas finas que enlazan una barra con la siguiente.
    'connectors' => true,
])
@php
    // Una marca NO DIBUJA: se apunta en el contexto y se calla. El gráfico dibuja cuando ya
    // tiene a todas, porque el dominio del eje es la unión de lo que aporten.
    app(\KoreUi\Charts\ChartContext::class)
        ->current('waterfall')
        ->add(
            (new \KoreUi\Charts\Marks\WaterfallMark($y, $label))
                ->withTotal($total)
                ->withConnectors(filter_var($connectors, FILTER_VALIDATE_BOOL))
                ->withVisible(filter_var($show, FILTER_VALIDATE_BOOL))
        );
@endphp
