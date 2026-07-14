{{-- La leyenda son BOTONES, no adornos: con ella se ocultan series. Y la identidad de la
     serie nunca depende solo del color — el nombre va escrito al lado. --}}
<ul class="kore-chart-legend" role="list">
    @foreach($series as $serie)
        <li>
            <button type="button"
                    class="kore-chart-legend-item"
                    data-kore-legend="{{ $serie['id'] }}"
                    x-on:click="toggleSeries('{{ $serie['id'] }}')"
                    x-bind:aria-pressed="isHidden('{{ $serie['id'] }}') ? 'false' : 'true'"
                    aria-pressed="true">
                <span class="kore-chart-legend-dot" style="--kore-series: {{ $serie['color'] }}"></span>
                <span>{{ $serie['name'] }}</span>
            </button>
        </li>
    @endforeach
</ul>
