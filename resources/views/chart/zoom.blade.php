{{-- El slider de contexto («navigator») y los controles del zoom.

     El slider es un segundo <svg> con la serie ENTERA. Y es, literalmente, gratis: un <path> son
     17 nodos de DOM pase lo que pase — con diez puntos o con diez mil. En una arquitectura que
     dibuja en el servidor, un gráfico de contexto no cuesta nada; en una de canvas es un segundo
     motor de dibujo.

     Los controles son <button> DE VERDAD. No es una concesión: es que ni ECharts, ni uPlot, ni
     Highcharts tienen un zoom navegable con teclado — el mantenedor de uPlot cerró la puerta a la
     accesibilidad por escrito. El arrastre es el atajo para quien tiene ratón, no el mecanismo.

     ⚠️ La propiedad de Alpine se llama `view`, no `window`: dentro de una expresión de Alpine,
     `window` sombrearía el objeto global del navegador. --}}

@if($zoom['slider'] ?? true)
    <div class="kore-chart-slider"
         x-on:pointerdown="onSliderDown($event, 'pan')"
         role="group"
         aria-label="{{ __('Tramo visible del gráfico') }}">

        {{-- La serie entera. Sin ejes, sin rejilla, sin tooltip: es contexto, no un gráfico. --}}
        <svg class="kore-chart-slider-svg" viewBox="0 0 100 100" preserveAspectRatio="none"
             aria-hidden="true" focusable="false">
            @foreach($overview as $trazo)
                <path class="kore-chart-slider-line" d="{{ $trazo['d'] }}"
                      style="--kore-series: {{ $trazo['color'] }}"
                      vector-effect="non-scaling-stroke"/>
            @endforeach
        </svg>

        {{-- Lo que queda FUERA de la ventana, atenuado. Una máscara a cada lado. --}}
        <div class="kore-chart-slider-mask" data-side="start" aria-hidden="true"
             :style="`--kw: ${view[0]}`"></div>
        <div class="kore-chart-slider-mask" data-side="end" aria-hidden="true"
             :style="`--kw: ${100 - view[1]}`"></div>

        {{-- La ventana. Es un <button> de verdad: entra en el tab order y con las flechas se
             desplaza. Highcharts y ECharts arrastran <div>s con mousedown; ninguno se puede usar
             con el teclado. --}}
        <button type="button"
                class="kore-chart-slider-window"
                x-on:keydown.arrow-left.prevent="nudge(-5)"
                x-on:keydown.arrow-right.prevent="nudge(5)"
                x-on:keydown.home.prevent="resetZoom()"
                :style="`--kx: ${view[0]}; --kw: ${view[1] - view[0]}`"
                :aria-label="`{{ __('Tramo visible') }}: ${Math.round(view[0])}% – ${Math.round(view[1])}%`">
            <span class="kore-chart-slider-handle" data-side="start"
                  x-on:pointerdown.stop="onSliderDown($event, 'from')" aria-hidden="true"></span>
            <span class="kore-chart-slider-handle" data-side="end"
                  x-on:pointerdown.stop="onSliderDown($event, 'to')" aria-hidden="true"></span>
        </button>
    </div>
@endif

<div class="kore-chart-zoom-controls">
    <button type="button" class="kore-chart-zoom-button"
            x-on:click="zoomBy(0.5)"
            aria-label="{{ __('Ampliar') }}" title="{{ __('Ampliar') }}">
        <x-kore::icon name="zoom-in" class="size-4" />
    </button>

    <button type="button" class="kore-chart-zoom-button"
            x-on:click="zoomBy(2)"
            :disabled="! zoomed"
            aria-label="{{ __('Reducir') }}" title="{{ __('Reducir') }}">
        <x-kore::icon name="zoom-out" class="size-4" />
    </button>

    <button type="button" class="kore-chart-zoom-button kore-chart-zoom-reset"
            x-on:click="resetZoom()"
            :disabled="! zoomed"
            x-text="zoomed ? '{{ __('Restablecer') }}' : '{{ __('Todo') }}'">{{ __('Todo') }}</button>
</div>
