@php
    use KoreUi\Charts\ChartContext;
    use KoreUi\Charts\Format;
    use KoreUi\Charts\Plot;
    use KoreUi\Charts\Time\TimeFormat;

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
        // Los meses se escriben en el idioma de la aplicación. Carbon los traduce con sus
        // propias tablas: ni `ext-intl`, ni un byte de `Intl` en el bundle.
        timeFormat: new TimeFormat(app()->getLocale()),
        xTickCount: $xAxis['ticks'] ?? null,
        continuousXTicks: (int) ($config['x_ticks'] ?? 6),
        yMin: $yAxis['min'] ?? null,
        yMax: $yAxis['max'] ?? null,
        // El tramo visible, en % del dominio COMPLETO. Dos números, no dos fechas — y eso es lo
        // que hace que el zoom no necesite ni una escala en JavaScript. Ver XScale::window().
        window: is_array($window) && count($window) === 2 ? [(float) $window[0], (float) $window[1]] : null,
    );

    // Los ejes salen POR DEFECTO: un gráfico sin ejes no se lee. La marca <chart.axis-*> no
    // los enciende, los CONFIGURA — o los apaga con :show="false".
    $showY = $yAxis['show'] ?? true;
    $showX = $xAxis['show'] ?? true;

    // La altura es una longitud CSS y va a un atributo `style`: se valida, o sería una vía
    // de inyección de CSS arbitrario (mismo patrón que SidebarState::cssLength).
    $height = \KoreUi\Shell\SidebarState::cssLength($height, $config['height'] ?? '16rem');
    $aspectSafe = $aspect !== null && preg_match('/^\d+(\.\d+)?\s*\/\s*\d+(\.\d+)?$/', trim($aspect)) === 1
        ? trim($aspect)
        : null;

    $donut = collect($plot->series)->firstWhere('type', 'donut');
    $gauge = collect($plot->series)->firstWhere('type', 'gauge');
    $funnel = collect($plot->series)->firstWhere('type', 'funnel');
    $heatmap = collect($plot->series)->firstWhere('type', 'heatmap');
    $cartesian = collect($plot->series)->reject(fn ($s) => $s['type'] === 'donut')->values()->all();
@endphp

@php
    // El componente Alpine solo se monta si hace falta: sin tooltip ni leyenda no hay nada
    // que interactuar y el gráfico funciona con cero JavaScript.
    //
    // El donut NUNCA lo monta: su única interacción —encender el arco y su fila de la
    // leyenda a la vez— es CSS puro (`:has()` sobre un `data-slice` común). Montarlo aquí
    // sería un x-data de propina que no hace absolutamente nada.
    // ⚠️ El zoom NO se apaga en el estado vacío, y que lo hiciera fue un bug feo: si amplías tanto
    // que la ventana se queda sin datos —o la metes dentro de un hueco de la serie—, sale el estado
    // vacío… y con él desaparecían los controles. Sin botón de restablecer, sin slider, sin nada:
    // no había forma de volver salvo tocar la URL a mano o recargar. Un callejón sin salida.
    //
    // Un gráfico puede quedarse sin datos que enseñar. Lo que no puede es quedarse sin salida.
    $zoom = $donut ? null : $frame->zoom;

    $zoomed = $plot->window !== null && ($plot->window[0] > 0.01 || $plot->window[1] < 99.99);

    // El stream sí vive aunque el gráfico esté vacío: es justamente lo que hace que un panel que
    // arranca sin datos se rellene solo cuando llegan.
    $stream = $donut ? null : $frame->stream;

    $interactive = ! $donut && ! $gauge && ! $funnel && ($heatmap || $zoom || $stream || (! $plot->empty && ($frame->tooltip || $frame->legend)));

    // El mini-gráfico de contexto necesita la serie ENTERA, no la de la ventana. Se recalcula el
    // Plot sin ventana — es PHP puro y no toca el frame. Y el resultado es UN <path> por serie:
    // 17 nodos de DOM pase lo que pase, con diez puntos o con diez mil. Un gráfico de contexto es
    // literalmente gratis en una arquitectura que dibuja en el servidor; en una de canvas es un
    // segundo motor.
    $overview = [];

    if ($zoom && ($zoom['slider'] ?? true)) {
        $full = new Plot(
            frame: $frame,
            format: Format::fromConfig($yAxis['format'] ?? null, $config['format'] ?? []),
            barPadding: (float) ($config['bar_padding'] ?? 0.2),
            timeFormat: new TimeFormat(app()->getLocale()),
        );

        foreach ($full->series as $serie) {
            if ($serie['type'] === 'donut') {
                continue;
            }

            $d = \KoreUi\Charts\Path::line($serie['points']);

            if ($d !== '') {
                $overview[] = ['d' => $d, 'color' => $serie['color']];
            }
        }
    }
@endphp

<div
    data-kore-chart="{{ $chartId }}"
    @if($plot->empty) data-kore-chart-empty="true" @endif
    @if($zoomed ?? false) data-kore-chart-zoomed="true" @endif
    {{-- ⚠️ Las transiciones sólo se encienden si NO hay trazo.

         El <path> no se puede animar (medido: WebKit ni soporta `transition: d`), así que salta de
         golpe. Y todo lo que se mueva despacio mientras el trazo salta SE DESPEGA DE ÉL: medido,
         con los puntos animados el peor se iba a 8,36 % del área de la curva sobre la que se supone
         que está. Unos 24 px. Se veía a la legua.

         Así que o se anima todo, o no se anima nada — y como el trazo no puede, no se anima nada. --}}
    @if(($stream ?? null) && ($stream['transition'] ?? true) && ! $frame->hasTrace()) data-kore-chart-stream="true" @endif
    @if($interactive)
        x-data="KoreChart({{ Js::from(['id' => $chartId, 'zoom' => $zoom, 'stream' => $stream]) }})"
        @if($frame->tooltip || $zoom)
            x-on:pointermove="onPointerMove($event)"
        @endif
        @if($frame->tooltip)
            x-on:pointerleave="onPointerLeave()"
        @endif
        @if($zoom)
            {{-- En `window` y no en el elemento: si el navegador no soporta setPointerCapture, el
                 pointerup se dispara donde esté el ratón — que puede ser fuera del gráfico— y el
                 arrastre se quedaría colgado para siempre. --}}
            x-on:pointerup.window="onDragEnd()"
            x-on:pointercancel.window="onDragEnd()"
        @endif
    @endif
    {{-- Con el eje Y apagado la canaleta mide 0: si no, la columna del grid seguiría
         reservando su ancho y el gráfico dejaría una franja vacía a la izquierda. --}}
    style="{{ $aspectSafe ? '--kore-chart-aspect: '.$aspectSafe.';' : '--kore-chart-height: '.$height.';' }} --kore-chart-gutter-y: {{ $plot->empty || ! $showY ? 0 : $plot->yGutter() }}ch;"
    {{ $attributes->except('class')->class(['kore-chart', $aspectSafe ? 'kore-chart-aspect' : null, $attributes->get('class')]) }}
>
    @if($title)
        <div class="kore-chart-title text-sm font-medium text-kore-fg">{{ $title }}</div>
    @endif

    @if($plot->empty)
        <x-kore::empty-state
            :title="$zoomed
                ? __('No hay datos en este tramo')
                : ($config['empty_text'] ?? 'No hay datos que mostrar')"
            :icon="$config['empty_icon'] ?? 'chart-line'"
        />

        @if($zoom)
            {{-- La ventana, para que el cliente sepa dónde está. Sin las series: no hay ninguna. --}}
            <script type="application/json" data-kore-chart-payload="{{ $chartId }}">@json($plot->payload(series: false))</script>

            @include('kore::chart.zoom', ['plot' => $plot, 'overview' => $overview, 'zoom' => $zoom])
        @endif
    @elseif($donut)
        @include('kore::chart.donut', ['plot' => $plot, 'donut' => $donut, 'chartId' => $chartId])
    @elseif($gauge)
        @include('kore::chart.gauge', ['gauge' => $gauge['gauge'], 'name' => $gauge['name']])
    @elseif($funnel)
        @include('kore::chart.funnel', ['stages' => $funnel['funnel'], 'highlight' => $funnel['highlight']])
    @elseif($heatmap)
        @include('kore::chart.heatmap', ['heatmap' => $heatmap['heatmap']])
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
            <div class="kore-chart-plot" x-ref="plot"
                 @if($zoom ?? false)
                     x-on:pointerdown="onBrushDown($event)"
                     x-on:dblclick="resetZoom()"
                 @endif>
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
                                         @if($stream ?? null)
                                             {{-- La clave sigue al DATO, no a su sitio en la lista.
                                                  Sin ella, en una ventana deslizante el morph
                                                  reutiliza la barra i para el dato i+1: la barra
                                                  CRECE en el sitio en vez de que la de al lado se
                                                  desplace, y con la transición puesta se ve
                                                  temblar. --}}
                                             wire:key="{{ $chartId }}-{{ $serie['id'] }}-{{ $plot->categories[$bar['index']] ?? $bar['index'] }}"
                                         @endif
                                         data-kore-serie="{{ $serie['id'] }}"
                                         data-index="{{ $bar['index'] }}"
                                         @if($bar['negative'] ?? false) data-negative="true" @endif
                                         {{-- En una cascada, el color codifica POLARIDAD (sube/baja/total),
                                              no identidad. Es el único sitio donde una serie usa los tokens
                                              semánticos, y es legítimo: verde = «esto suma». --}}
                                         @if($bar['variant'] ?? false) data-variant="{{ $bar['variant'] }}" @endif
                                         {{-- Sólo la PUNTA de la columna se redondea. Ver Plot::layoutBars(). --}}
                                         @if($bar['cap']) data-cap="true" @endif
                                         style="--kore-series: {{ $serie['color'] }}; --kx: {{ $bar['x'] }}; --kw: {{ $bar['w'] }}; --ky: {{ $bar['y'] }}; --kh: {{ $bar['h'] }}"></div>
                                @endforeach

                                {{-- Los conectores de la cascada: una línea fina al nivel donde cada
                                     barra se toca con la siguiente. Enlazan visualmente el salto. --}}
                                @if($serie['connectors_on'] ?? false)
                                    @foreach($serie['connectors'] as $conn)
                                        <div class="kore-chart-connector" aria-hidden="true"
                                             style="--kx: {{ $conn['x'] }}; --kw: {{ $conn['w'] }}; --ky: {{ $conn['y'] }}"></div>
                                    @endforeach
                                @endif
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

                @if($zoom ?? false)
                    {{-- El rectángulo del brush. Es un <div> con dos custom properties: no hay que
                         dibujar nada, solo decirle al CSS dónde empieza y cuánto mide. --}}
                    <div class="kore-chart-brush" x-show="brush !== null" x-cloak aria-hidden="true"
                         :style="brush ? `--kx: ${brush.from}; --kw: ${brush.to - brush.from}` : ''"></div>
                @endif

                @if($frame->tooltip)
                    @include('kore::chart.tooltip', ['plot' => $plot, 'chartId' => $chartId])
                @endif
            </div>

            {{-- El payload. Va en un <script type="application/json">, no en un atributo: el JSON
                 dentro de un atributo HTML escapa cada comilla a &quot; — seis bytes por comilla.

                 Y va AQUÍ y no en el `x-data`, porque el morph de Livewire sí reescribe este
                 <script> pero NO reinicializa el x-data: un `x-data` con la ventana dentro se
                 quedaría con la de antes del zoom.

                 Sin tooltip solo lleva la ventana: el dato es una segunda copia entera en el DOM y
                 a 2.000 puntos pesa más que el propio <path>. --}}
            @if($frame->tooltip || $zoom)
                <script type="application/json" data-kore-chart-payload="{{ $chartId }}">@json($plot->payload((bool) $frame->tooltip))</script>
            @endif

            @if($showX)
                <div class="kore-chart-gutter-x" aria-hidden="true">
                    @foreach($plot->xTicks as $tick)
                        {{-- `--ktw` es lo que MIDE la etiqueta, en `ch`. Con eso, el CSS la centra
                             sobre su tick pero la ACOTA dentro del área — el servidor no puede
                             medir texto, pero sí contarlo, y el navegador convierte el `ch` con la
                             fuente real. Ver TextWidth y el trait TickBox.

                             `context` es la segunda línea: el mes en un eje de días, el año en uno
                             de meses. Solo la llevan el primer tick y aquellos en que cambia. Sin
                             ella, un eje del 10 al 20 de enero no dice en ninguna parte de qué mes
                             habla — ningún tick cae en un día 1. Es el agujero que tiene d3. --}}
                        <span class="kore-chart-tick"
                              style="--kx: {{ $tick['pos'] }}; --ktw: {{ $tick['width'] }}; --kroom: {{ $tick['room'] ?? 100 }}">{{ $tick['label'] }}@if($tick['context'] ?? null)<span class="kore-chart-tick-context">{{ $tick['context'] }}</span>@endif</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if($zoom ?? false)
            @include('kore::chart.zoom', ['plot' => $plot, 'overview' => $overview, 'zoom' => $zoom])
        @endif

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
