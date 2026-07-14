{{-- El embudo no es cartesiano: no tiene ejes ni escala. Cada etapa es un trapecio (un <div>
     recortado con clip-path) apilado sobre el siguiente, y la etiqueta va al lado. --}}
{{-- `data-stage` es el mismo en el trapecio y en su fila de la lista: al posarse sobre cualquiera
     de los dos, `:has()` enciende el par y apaga el resto — SIN una línea de JavaScript, igual que
     el donut. Con muchas etapas, es lo que deja claro qué trapecio es «Carrito». --}}
<div class="kore-chart-funnel" @if($highlight ?? true) data-highlight @endif>
    <div class="kore-chart-funnel-plot" role="img" aria-label="Embudo de conversión">
        @foreach($stages as $stage)
            <div class="kore-chart-funnel-stage"
                 data-stage="{{ $loop->index }}"
                 style="--kore-series: {{ $stage['color'] }};
                        top: {{ $stage['top'] }}%;
                        height: {{ $stage['height'] }}%;
                        clip-path: {{ $stage['clip'] }}"></div>
        @endforeach
    </div>

    {{-- Los números al lado, no encima: un trapecio estrecho no tiene sitio dentro para el texto,
         y meterlo lo dejaría ilegible en las últimas etapas. --}}
    <ul class="kore-chart-funnel-labels" role="list">
        @foreach($stages as $stage)
            <li class="kore-chart-funnel-label"
                data-stage="{{ $loop->index }}"
                style="--kore-series: {{ $stage['color'] }}">
                <span class="kore-chart-funnel-dot" aria-hidden="true"></span>
                <span class="kore-chart-funnel-name">{{ $stage['label'] }}</span>
                <span class="kore-chart-funnel-value">{{ $stage['formatted'] }}</span>
                <span class="kore-chart-funnel-percent">{{ $stage['percent'] }} %</span>
                @if($stage['drop'] !== null)
                    <span class="kore-chart-funnel-drop">−{{ $stage['drop'] }} %</span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
