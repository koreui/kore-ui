{{-- El donut vive en su propio SVG CUADRADO y con escalado uniforme: un arco sí se
     deformaría con preserveAspectRatio="none". Por eso no comparte caja con lo cartesiano. --}}
<div class="kore-chart-donut">
    <svg viewBox="0 0 100 100" role="img" aria-hidden="true" focusable="false">
        @foreach($donut['slices'] as $slice)
            <path class="kore-chart-slice"
                  d="{{ $slice['path'] }}"
                  style="--kore-series: var(--kore-chart-{{ $slice['slot'] }})"
                  data-index="{{ $loop->index }}"/>
        @endforeach
    </svg>

    <ul class="kore-chart-legend kore-chart-legend-vertical" role="list">
        @foreach($donut['slices'] as $slice)
            <li class="kore-chart-legend-item">
                <span class="kore-chart-legend-dot" style="--kore-series: var(--kore-chart-{{ $slice['slot'] }})"></span>
                <span>{{ $slice['label'] }}</span>
                <span class="kore-chart-legend-value">{{ $slice['formatted'] }}</span>
                <span class="kore-chart-legend-percent">{{ $slice['percent'] }} %</span>
            </li>
        @endforeach
    </ul>
</div>
