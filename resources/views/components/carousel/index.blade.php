@props([
    'autoplay' => null,
    'interval' => null,
    'loop' => null,
    'pauseOnHover' => null,
    'showIndicators' => null,
    'showNavigation' => null,
    'numVisible' => null,
    'gap' => null,
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
@endphp

<div {{ $attributes->class(['relative w-full']) }}
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

    {{-- Viewport --}}
    <div class="overflow-hidden" x-ref="viewport">
        <div x-ref="track"
             class="flex transition-transform duration-300 ease-out"
             style="gap: {{ $gap }}px;"
             x-on:pointerdown.prevent="onPointerDown($event)"
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
                aria-label="Previous slide">
            <x-lucide-chevron-left class="size-5" />
        </button>
        <button type="button"
                x-on:click="next()"
                x-show="loop || currentIndex < totalSlides - numVisible"
                class="absolute right-2 top-1/2 -translate-y-1/2 z-10 inline-flex items-center justify-center size-9 rounded-full bg-kore-surface/80 text-kore-surface-fg border border-kore-border shadow-sm backdrop-blur-sm hover:bg-kore-surface transition-colors focus:outline-none focus:ring-2 focus:ring-kore-ring"
                aria-label="Next slide">
            <x-lucide-chevron-right class="size-5" />
        </button>
    @endif

    {{-- Indicators --}}
    @if($showIndicators)
        <div class="flex items-center justify-center gap-1.5 mt-4" role="tablist" aria-label="Slide indicators">
            <template x-for="i in totalPages" :key="i">
                <button type="button"
                        x-on:click="goTo((i - 1) * numVisible)"
                        :class="currentPage === i - 1
                            ? 'bg-kore-primary w-6'
                            : 'bg-kore-border w-2 hover:bg-kore-muted-fg'"
                        class="h-2 rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-kore-ring"
                        role="tab"
                        :aria-selected="currentPage === i - 1"
                        :aria-label="'Go to slide group ' + i">
                </button>
            </template>
        </div>
    @endif

    {{-- Footer slot --}}
    @if(isset($footer))
        <div class="mt-3">
            {{ $footer }}
        </div>
    @endif
</div>
