{{-- Barras horizontales: el mismo gráfico de barras, transpuesto. La categoría baja por la
     izquierda (donde caben las etiquetas largas sin rotar) y el valor corre por abajo. Las barras
     son los MISMOS <div> con `--kx/--kw/--ky/--kh` que en vertical —la geometría se calculó una vez
     en Plot::layoutBars, sólo que con los ejes intercambiados—, así que reusan su CSS tal cual.

     No lleva Alpine: es barras y nada más (lo garantiza ChartFrame::validate), y el resalte al pasar
     el ratón es `:hover` de CSS puro. Sin tooltip flotante: un gráfico de barras se lee del eje y la
     rejilla, que es justo lo que print lleva un siglo haciendo. --}}
@if($frame->legend && ($frame->legend['position'] ?? 'top') === 'top')
    @include('kore::chart.legend', ['series' => $series, 'chartId' => $chartId])
@endif

<div class="kore-chart-frame">
    {{-- Las categorías, a la izquierda. Una etiqueta por banda, centrada en su fila con `--ky`. Si
         no caben en la canaleta (Plot::bandGutter la acota a 22ch), el CSS las corta con puntos
         suspensivos. --}}
    @if($showY)
        <div class="kore-chart-gutter-y" aria-hidden="true">
            @foreach($plot->bandTicks() as $tick)
                <span class="kore-chart-tick" style="--ky: {{ $tick['pos'] }}">{{ $tick['label'] }}</span>
            @endforeach
        </div>
    @endif

    {{-- ⚠️ MISMO CONTRATO que el cartesiano: esta caja no lleva padding ni borde. El 0..100 de las
         barras es exactamente el 0%..100% de la caja. --}}
    <div class="kore-chart-plot">
        {{-- La rejilla, ahora VERTICAL: una línea por cada tick del valor, a la altura de su `--kx`.
             El CSS la tumba (top/bottom) bajo el atributo de orientación del contenedor. --}}
        @if($plot->frame->grid)
            <div class="kore-chart-grid" aria-hidden="true">
                @foreach($plot->valueTicks() as $tick)
                    <div class="kore-chart-grid-line" style="--kx: {{ $tick['pos'] }}"></div>
                @endforeach
            </div>
        @endif

        {{-- Una capa por serie. En horizontal sólo hay barras (HTML), nunca trazo SVG. --}}
        <div class="kore-chart-marks" aria-hidden="true">
            @foreach($series as $serie)
                @foreach($serie['bars'] as $bar)
                    <div class="kore-chart-bar"
                         data-kore-serie="{{ $serie['id'] }}"
                         data-index="{{ $bar['index'] }}"
                         @if($bar['negative'] ?? false) data-negative="true" @endif
                         {{-- Sólo la PUNTA (el extremo del valor) se redondea. En horizontal la punta
                              es el borde derecho —o el izquierdo si es negativa—; el CSS lo resuelve
                              con el atributo de orientación. --}}
                         @if($bar['cap']) data-cap="true" @endif
                         style="--kore-series: {{ $serie['color'] }}; --kx: {{ $bar['x'] }}; --kw: {{ $bar['w'] }}; --ky: {{ $bar['y'] }}; --kh: {{ $bar['h'] }}"></div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- El valor, abajo. Cada etiqueta centrada sobre su tick con `--kx` y acotada con `--ktw` (lo
         que mide en `ch`) para que no se salga de la caja — mismo truco que el eje X cartesiano. --}}
    @if($showX)
        <div class="kore-chart-gutter-x" aria-hidden="true">
            @foreach($plot->valueTicks() as $tick)
                <span class="kore-chart-tick"
                      style="--kx: {{ $tick['pos'] }}; --ktw: {{ $tick['width'] }}; --kroom: {{ $tick['room'] }}">{{ $tick['label'] }}</span>
            @endforeach
        </div>
    @endif
</div>
