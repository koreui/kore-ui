@php
    use KoreUi\Charts\ChartContext;
    use KoreUi\Charts\Format;
    use KoreUi\Charts\Plot;

    // Este @php corre DESPUÉS de los hijos (Blade renderiza el slot antes que la plantilla
    // que lo contiene), así que aquí el frame ya tiene todas las marcas. Ver el aviso de
    // KoreUi\View\Components\Chart: en render() no habría ninguna.
    $frame = app(ChartContext::class)->close();

    $config = config('kore-ui.chart', []);

    $yAxis = $frame->axes['y'] ?? [];
    $xAxis = $frame->axes['x'] ?? [];

    $plot = new Plot(
        frame: $frame,
        format: Format::fromConfig($yAxis['format'] ?? null, $config['format'] ?? []),
        tickCount: $yAxis['ticks'] ?? ($config['ticks'] ?? 5),
        barPadding: (float) ($config['bar_padding'] ?? 0.2),
        maxXLabels: $xAxis['max_labels'] ?? ($config['max_x_labels'] ?? 12),
    );

    $showY = ! ($yAxis['hide'] ?? false);
    $showX = ! ($xAxis['hide'] ?? false);

    // La altura es una longitud CSS y va a un atributo `style`: se valida, o sería una vía
    // de inyección de CSS arbitrario (mismo patrón que SidebarState::cssLength).
    $height = \KoreUi\Shell\SidebarState::cssLength($height, $config['height'] ?? '16rem');
    $aspectSafe = $aspect !== null && preg_match('/^\d+(\.\d+)?\s*\/\s*\d+(\.\d+)?$/', trim($aspect)) === 1
        ? trim($aspect)
        : null;

    $donut = collect($plot->series)->firstWhere('type', 'donut');
    $cartesian = collect($plot->series)->reject(fn ($s) => $s['type'] === 'donut')->values()->all();
@endphp

@php
    // El componente Alpine solo se monta si hace falta: sin tooltip ni leyenda no hay nada
    // que interactuar y el gráfico funciona con cero JavaScript.
    $interactive = ! $plot->empty && ($frame->tooltip || $frame->legend);
@endphp

<div
    data-kore-chart="{{ $chartId }}"
    @if($plot->empty) data-kore-chart-empty="true" @endif
    @if($interactive)
        x-data="KoreChart({{ Js::from(['id' => $chartId]) }})"
        @if($frame->tooltip)
            x-on:pointermove="onPointerMove($event)"
            x-on:pointerleave="onPointerLeave()"
        @endif
    @endif
    style="{{ $aspectSafe ? '--kore-chart-aspect: '.$aspectSafe.';' : '--kore-chart-height: '.$height.';' }} --kore-chart-gutter-y: {{ $plot->empty ? 2 : $plot->yGutter() }}ch;"
    {{ $attributes->except('class')->class(['kore-chart', $aspectSafe ? 'kore-chart-aspect' : null, $attributes->get('class')]) }}
>
    @if($title)
        <div class="kore-chart-title text-sm font-medium text-kore-fg">{{ $title }}</div>
    @endif

    @if($plot->empty)
        <x-kore::empty-state
            :title="$config['empty_text'] ?? 'No hay datos que mostrar'"
            :icon="$config['empty_icon'] ?? 'chart-line'"
        />
    @elseif($donut)
        @include('kore::chart.donut', ['plot' => $plot, 'donut' => $donut, 'chartId' => $chartId])
    @else
        @if($frame->legend && ($frame->legend['position'] ?? 'top') === 'top')
            @include('kore::chart.legend', ['series' => $cartesian, 'chartId' => $chartId])
        @endif

        {{-- El grid de 3 zonas. El ancho de la canaleta del eje Y sale de CONTAR CARACTERES en
             PHP y expresarlo en `ch`: el servidor no puede medir texto, pero el navegador sí
             convierte `ch` a píxeles con la fuente real. (Dejarla en `auto` no funciona: las
             etiquetas van en position:absolute —cada una a la altura de su tick— así que no
             aportan anchura y la columna colapsaría.) Y como las posiciones del dato son % del
             área RESTANTE, que la canaleta mida más o menos no invalida ninguna coordenada. --}}
        <div class="kore-chart-frame">
            @if($showY)
                <div class="kore-chart-gutter-y" aria-hidden="true">
                    @foreach($plot->yTicks as $tick)
                        <span class="kore-chart-tick" style="--ky: {{ $tick['pos'] }}">{{ $tick['label'] }}</span>
                    @endforeach
                </div>
            @endif

            {{-- ⚠️ CONTRATO: esta caja no lleva padding, ni borde, ni margen. El 0..100 del
                 viewBox del SVG es EXACTAMENTE el 0%..100% de esta caja y el de las capas HTML.
                 Un `p-2` aquí desalinea las barras de la línea y los ticks de la rejilla. El aire
                 alrededor del dato se consigue en el DOMINIO (nice()), nunca en la caja. --}}
            <div class="kore-chart-plot" x-ref="plot">
                @if($plot->frame->grid)
                    <div class="kore-chart-grid" aria-hidden="true">
                        @foreach($plot->yTicks as $tick)
                            <div class="kore-chart-grid-line" style="--ky: {{ $tick['pos'] }}"></div>
                        @endforeach
                    </div>
                @endif

                {{-- Una capa por cada TRAMO CONTIGUO de marcas del mismo medio. El orden del DOM
                     es el orden de pintado, así que el orden en que escribes las marcas es el
                     orden en que se pintan — que es el contrato de una API de marcas. --}}
                @foreach($plot->layers as $layer)
                    @if($layer['medium'] === 'svg')
                        {{-- ⚠️ Aquí dentro SOLO hay <path>. Ni <text>, ni <circle>, ni <rect>:
                             con preserveAspectRatio="none" todo lo demás se deforma. El trazo no,
                             gracias a vector-effect. Y overflow:visible porque el punto máximo cae
                             en y=0 y se le recortaría medio trazo. --}}
                        <svg class="kore-chart-svg" viewBox="0 0 100 100" preserveAspectRatio="none"
                             aria-hidden="true" focusable="false">
                            @foreach($layer['series'] as $serie)
                                @if($serie['area'])
                                    <path class="kore-chart-area" d="{{ $serie['area'] }}"
                                          style="--kore-series: {{ $serie['color'] }}"
                                          data-kore-serie="{{ $serie['id'] }}"/>
                                @endif
                                @if($serie['d'])
                                    <path class="kore-chart-line" d="{{ $serie['d'] }}"
                                          style="--kore-series: {{ $serie['color'] }}"
                                          data-kore-serie="{{ $serie['id'] }}"
                                          vector-effect="non-scaling-stroke"/>
                                @endif
                            @endforeach
                        </svg>
                    @else
                        <div class="kore-chart-marks" aria-hidden="true">
                            @foreach($layer['series'] as $serie)
                                @foreach($serie['bars'] as $bar)
                                    <div class="kore-chart-bar"
                                         data-kore-serie="{{ $serie['id'] }}"
                                         data-index="{{ $bar['index'] }}"
                                         @if($bar['negative']) data-negative="true" @endif
                                         style="--kore-series: {{ $serie['color'] }}; --kx: {{ $bar['x'] }}; --kw: {{ $bar['w'] }}; --ky: {{ $bar['y'] }}; --kh: {{ $bar['h'] }}"></div>
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                @endforeach

                {{-- Los puntos son opt-in: un <div> por punto escala 1:1 con el dato. --}}
                @foreach($cartesian as $serie)
                    @if($serie['dots'])
                        <div class="kore-chart-marks" aria-hidden="true">
                            @foreach($serie['dots'] as $dot)
                                <div class="kore-chart-dot"
                                     data-kore-serie="{{ $serie['id'] }}"
                                     data-index="{{ $dot['index'] }}"
                                     style="--kore-series: {{ $serie['color'] }}; --kx: {{ $dot['x'] }}; --ky: {{ $dot['y'] }}"></div>
                            @endforeach
                        </div>
                    @endif
                @endforeach

                @if($frame->tooltip)
                    @include('kore::chart.tooltip', ['plot' => $plot, 'chartId' => $chartId])
                @endif
            </div>

            @if($showX)
                <div class="kore-chart-gutter-x" aria-hidden="true">
                    @foreach($plot->xTicks as $tick)
                        <span class="kore-chart-tick" style="--kx: {{ $tick['pos'] }}">{{ $tick['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if($frame->legend && ($frame->legend['position'] ?? 'top') === 'bottom')
            @include('kore::chart.legend', ['series' => $cartesian, 'chartId' => $chartId])
        @endif
    @endif

    {{-- Los datos, en una tabla. Un <svg> es tan mudo para un lector de pantalla como un
         <canvas>: la respuesta correcta es servir los datos, y nosotros los tenemos en PHP,
         así que sale gratis. Nadie en el ecosistema lo hace. --}}
    @unless($plot->empty)
        @include('kore::chart.table', ['plot' => $plot, 'label' => $ariaLabel ?? $title])
    @endunless
</div>
