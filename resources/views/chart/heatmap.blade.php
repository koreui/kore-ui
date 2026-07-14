<div class="kore-chart-heatmap">
    <div class="kore-chart-heatmap-frame">
        {{-- Las filas, a la izquierda. Una etiqueta por fila, centrada en su banda. --}}
        <div class="kore-chart-heatmap-rows" aria-hidden="true">
            @foreach($heatmap['rowTicks'] as $tick)
                <span class="kore-chart-tick" style="--pos: {{ $tick['pos'] }}">{{ $tick['label'] }}</span>
            @endforeach
        </div>

        {{-- La rejilla. UN solo pointermove en el plot (delegación): con 365×7 celdas, poner un
             listener por celda sería letal. El manejador lee el data-* de la celda bajo el ratón. --}}
        <div class="kore-chart-heatmap-plot" x-ref="plot"
             x-on:pointermove="onHeatmapMove($event)"
             x-on:pointerleave="onPointerLeave()">
            @foreach($heatmap['cells'] as $cell)
                <div class="kore-chart-heatmap-cell"
                     data-heat-cell
                     @if($cell['bucket'] !== null) data-bucket="{{ $cell['bucket'] }}" @endif
                     data-r="{{ $cell['row'] }}"
                     data-c="{{ $cell['col'] }}"
                     data-v="{{ $cell['label'] }}"
                     style="left: {{ $cell['x'] }}%; top: {{ $cell['y'] }}%; width: {{ $cell['w'] }}%; height: {{ $cell['h'] }}%"></div>
            @endforeach
        </div>

        {{-- Las columnas, debajo. Adelgazadas si son muchas (24 horas se pisan en un móvil), y cada
             una acotada al hueco que tiene hasta la siguiente — mismo truco que el eje cartesiano. --}}
        <div class="kore-chart-heatmap-cols" aria-hidden="true">
            @foreach($heatmap['colTicks'] as $tick)
                <span class="kore-chart-tick" style="--pos: {{ $tick['pos'] }}; --kroom: {{ $tick['room'] }}">{{ $tick['label'] }}</span>
            @endforeach
        </div>
    </div>

    {{-- La leyenda: una barra discreta de menos a más. No es continua a propósito — el color se
         cuantiza, así que la leyenda enseña los mismos escalones que las celdas. --}}
    <div class="kore-chart-heatmap-legend" aria-hidden="true">
        <span class="kore-chart-heatmap-legend-min">{{ $heatmap['minLabel'] }}</span>
        <span class="kore-chart-heatmap-legend-scale">
            @for($b = 1; $b <= $heatmap['buckets']; $b++)
                <span data-bucket="{{ (int) round((($b - 1) / max(1, $heatmap['buckets'] - 1)) * 6) + 1 }}"></span>
            @endfor
        </span>
        <span class="kore-chart-heatmap-legend-max">{{ $heatmap['maxLabel'] }}</span>
    </div>

    {{-- El tooltip, movido por la delegación. wire:ignore como el cartesiano: floating-ui escribe
         style inline y el morph lo borraría. --}}
    <div wire:ignore class="kore-chart-tooltip" x-ref="tooltip" x-show="cell" x-cloak role="tooltip">
        <div class="kore-chart-tooltip-title" x-text="cell ? `${cell.col} · ${cell.row}` : ''"></div>
        <div class="kore-chart-tooltip-row">
            <span class="kore-chart-tooltip-value" x-text="cell?.value"></span>
        </div>
    </div>
</div>
