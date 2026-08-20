@props([])

{{-- `aria-roledescription="slide"` es lo que convierte un grupo cualquiera en
     una diapositiva para el lector. El `aria-label` con la posición —«2 / 4»— lo
     pone el JavaScript: un componente anónimo no sabe ni cuál es su índice ni
     cuántos hermanos tiene, y con el servidor añadiendo diapositivas el número
     cambia. --}}
<div {{ $attributes->class(['flex-shrink-0']) }}
     data-carousel-slide
     role="group"
     aria-roledescription="slide">
    {{ $slot }}
</div>
