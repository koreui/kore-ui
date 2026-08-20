@props([
    'autoplay' => null,
    'interval' => null,
    'loop' => null,
    'pauseOnHover' => null,
    'showIndicators' => null,
    'showNavigation' => null,
    'numVisible' => null,
    'gap' => null,
    'ariaLabel' => null,
])

@php
    $autoplay = $autoplay ?? config('kore-ui.ui.carousel.autoplay', false);
    $interval = (int) ($interval ?? config('kore-ui.ui.carousel.interval', 5000));
    $loop = $loop ?? config('kore-ui.ui.carousel.loop', true);
    $pauseOnHover = $pauseOnHover ?? config('kore-ui.ui.carousel.pause_on_hover', true);
    $showIndicators = $showIndicators ?? config('kore-ui.ui.carousel.show_indicators', true);
    $showNavigation = $showNavigation ?? config('kore-ui.ui.carousel.show_navigation', true);
    $numVisible = (int) ($numVisible ?? config('kore-ui.ui.carousel.num_visible', 1));
    $gap = (int) ($gap ?? config('kore-ui.ui.carousel.gap', 16));

    $t = fn (string $clave, string $porDefecto) => config("kore-ui.ui.translations.{$clave}", $porDefecto);

    // Un `role="region"` sin nombre no se anuncia como región: el lector se
    // queda con el `aria-roledescription` y nada que lo distinga de los otros
    // carruseles de la página. Es el mismo fallo que tenía `role="tree"`.
    $ariaLabel = $ariaLabel ?? $t('carousel', 'Carrusel');
@endphp

<div {{ $attributes->except(['ariaLabel'])->class(['relative w-full']) }}
     x-data="KoreCarousel({
        autoplay: {{ $autoplay ? 'true' : 'false' }},
        interval: {{ $interval }},
        loop: {{ $loop ? 'true' : 'false' }},
        pauseOnHover: {{ $pauseOnHover ? 'true' : 'false' }},
        numVisible: {{ $numVisible }},
        gap: {{ $gap }},
     })"
     role="region"
     aria-roledescription="carousel"
     aria-label="{{ $ariaLabel }}"
     x-on:keydown="onKeydown($event)"
     @if($autoplay)
     {{-- El puntero no es la única forma de estar «dentro»: sin esto, tabular
          por las diapositivas mientras el carrusel avanza solo mueve el
          contenido bajo el foco. --}}
     x-on:focusin="pause()"
     x-on:focusout="resume()"
     @endif
     @if($pauseOnHover)
     x-on:mouseenter="pause()"
     x-on:mouseleave="resume()"
     @endif>

    {{-- Header slot --}}
    @if(isset($header))
        <div class="mb-3">
            {{ $header }}
        </div>
    @endif

    {{-- Viewport. `aria-live` solo cuando el carrusel NO se mueve solo: con
         autoplay, un lector estaría anunciando cada diapositiva sin parar. --}}
    <div class="overflow-hidden"
         x-ref="viewport"
         @if(! $autoplay) aria-live="polite" @endif>
        <div x-ref="track"
             class="flex transition-transform duration-300 ease-out"
             style="gap: {{ $gap }}px;"
             x-on:pointerdown="onPointerDown($event)"
             x-on:pointermove="onPointerMove($event)"
             x-on:pointerup="onPointerUp($event)"
             x-on:pointercancel="onPointerUp($event)">
            {{ $slot }}
        </div>
    </div>

    {{-- Navigation --}}
    @if($showNavigation)
        <button type="button"
                x-on:click="prev()"
                x-show="loop || currentIndex > 0"
                class="absolute left-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center size-9 rounded-full bg-kore-surface/80 text-kore-surface-fg border border-kore-border shadow-sm backdrop-blur-sm hover:bg-kore-surface transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring"
                aria-label="{{ $t('carousel_previous', 'Diapositiva anterior') }}">
            <x-lucide-chevron-left class="size-5" />
        </button>
        <button type="button"
                x-on:click="next()"
                x-show="loop || currentIndex < totalSlides - numVisible"
                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center size-9 rounded-full bg-kore-surface/80 text-kore-surface-fg border border-kore-border shadow-sm backdrop-blur-sm hover:bg-kore-surface transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring"
                aria-label="{{ $t('carousel_next', 'Diapositiva siguiente') }}">
            <x-lucide-chevron-right class="size-5" />
        </button>
    @endif

    {{-- Indicadores y control de reproducción.

         Los indicadores NO son pestañas: no había ningún `role="tabpanel"` al
         otro lado, y con `numVisible` mayor que uno cada punto lleva a un GRUPO
         de diapositivas, no a una. Son botones con `aria-current`, que es lo que
         de verdad son. --}}
    @if($showIndicators || $autoplay)
        <div class="flex items-center justify-center gap-1.5 mt-4">
            @if($autoplay)
                {{-- WCAG 2.2.2: cualquier movimiento automático de más de cinco
                     segundos necesita una forma de pararlo. Pausar al pasar el
                     ratón por encima no vale para quien no usa ratón. --}}
                <button type="button"
                        x-on:click="toggleParado()"
                        class="mr-1.5 inline-flex items-center justify-center size-6 rounded-full text-kore-muted-fg hover:bg-kore-muted hover:text-kore-fg transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring"
                        x-bind:aria-label="parado
                            ? @js($t('carousel_play', 'Reanudar el carrusel'))
                            : @js($t('carousel_pause', 'Parar el carrusel'))">
                    <x-lucide-play class="size-3.5" x-show="parado" x-cloak />
                    <x-lucide-pause class="size-3.5" x-show="! parado" />
                </button>
            @endif

            @if($showIndicators)
                <template x-for="i in totalPages" :key="i">
                    <button type="button"
                            x-on:click="goTo((i - 1) * numVisible)"
                            :class="currentPage === i - 1
                                ? 'bg-kore-primary w-6'
                                : 'bg-kore-border w-2 hover:bg-kore-muted-fg'"
                            class="h-2 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-kore-ring"
                            :aria-current="currentPage === i - 1 ? 'true' : null"
                            :aria-label="@js($t('carousel_go_to', 'Ir al grupo')) + ' ' + i">
                    </button>
                </template>
            @endif
        </div>
    @endif

    {{-- Footer slot --}}
    @if(isset($footer))
        <div class="mt-3">
            {{ $footer }}
        </div>
    @endif
</div>
