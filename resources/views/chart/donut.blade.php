{{-- El donut vive en su propio SVG CUADRADO y con escalado uniforme: un arco sí se
     deformaría con preserveAspectRatio="none". Por eso no comparte caja con lo cartesiano. --}}
{{-- `data-slice` es el mismo en el arco y en su fila de la leyenda: es lo que permite
     enlazarlos al pasar el ratón SIN una línea de JavaScript (ver kore-theme.css). Sin ese
     enlace, para saber qué porción es «Abr» hay que emparejar el color a ojo entre el arco y
     la leyenda — que es justo lo que peor funciona, y peor todavía con daltonismo.

     Todas las reglas de `:has()` cuelgan de `data-highlight`, así que apagarlo (`:highlight="false"`
     o `kore-ui.chart.donut_highlight`) las desactiva enteras. No hay nada que "desmontar":
     no había JavaScript que montar. --}}
<div class="kore-chart-donut" @if($donut['highlight'] ?? true) data-highlight @endif>
    <svg viewBox="0 0 100 100" role="img" aria-hidden="true" focusable="false">
        @foreach($donut['slices'] as $slice)
            <path class="kore-chart-slice"
                  d="{{ $slice['path'] }}"
                  style="--kore-series: var(--kore-chart-{{ $slice['slot'] }})"
                  data-slice="{{ $loop->index }}"/>
        @endforeach
    </svg>

    <ul class="kore-chart-legend kore-chart-legend-vertical" role="list">
        @foreach($donut['slices'] as $slice)
            <li class="kore-chart-legend-item" data-slice="{{ $loop->index }}">
                <span class="kore-chart-legend-dot" style="--kore-series: var(--kore-chart-{{ $slice['slot'] }})"></span>
                <span>{{ $slice['label'] }}</span>
                <span class="kore-chart-legend-value">{{ $slice['formatted'] }}</span>
                <span class="kore-chart-legend-percent">{{ $slice['percent'] }} %</span>
            </li>
        @endforeach
    </ul>
</div>
