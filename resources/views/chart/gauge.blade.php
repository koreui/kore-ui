{{-- El gauge vive en su propio SVG CUADRADO y con escalado uniforme (como el donut): un arco se
     deformaría con preserveAspectRatio="none", y aquí sí hay <circle>/<line> además de <path>,
     que también se torcerían. --}}
<div class="kore-chart-gauge" style="--kore-gauge: {{ $gauge['color'] }}">
    <svg viewBox="0 0 100 100" role="img"
         aria-label="{{ $name }}: {{ $gauge['formatted'] }}">
        {{-- El fondo: el arco entero del rango, apagado. --}}
        <path class="kore-chart-gauge-track" d="{{ $gauge['track'] }}" fill="none" pathLength="1"/>

        {{-- El valor: el arco desde el principio hasta donde llega, con el color de su banda. --}}
        @if($gauge['arc'])
            <path class="kore-chart-gauge-arc" d="{{ $gauge['arc'] }}" fill="none"/>
        @endif

        {{-- Los pellizcos donde empieza cada banda de color. Sin ellos, el número no dice si está
             bien o mal — y entonces esto es un stat tile con un anillo, no un gauge. --}}
        @foreach($gauge['ticks'] as $tick)
            <line class="kore-chart-gauge-tick"
                  x1="{{ $tick['x1'] }}" y1="{{ $tick['y1'] }}"
                  x2="{{ $tick['x2'] }}" y2="{{ $tick['y2'] }}"/>
        @endforeach
    </svg>

    {{-- El número, en el hueco del arco. Va aparte del SVG —es HTML— para que la tipografía sea la
         de la app y no se deforme con el viewBox. --}}
    <div class="kore-chart-gauge-label" aria-hidden="true">
        <span class="kore-chart-gauge-value">{{ $gauge['formatted'] }}</span>
        @if($gauge['caption'])
            <span class="kore-chart-gauge-caption">{{ $gauge['caption'] }}</span>
        @endif
    </div>
</div>
