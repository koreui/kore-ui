# Carousel

Componente de carrusel con navegación, indicadores, autoplay, swipe y soporte multi-slide.

## Uso básico

```blade
<x-kore::carousel>
    <x-kore::carousel.slide>Slide 1</x-kore::carousel.slide>
    <x-kore::carousel.slide>Slide 2</x-kore::carousel.slide>
    <x-kore::carousel.slide>Slide 3</x-kore::carousel.slide>
</x-kore::carousel>
```

## Props del contenedor

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `autoplay` | `bool` | `config(false)` | Reproducción automática |
| `interval` | `int` | `config(5000)` | Intervalo de autoplay en ms |
| `loop` | `bool` | `config(true)` | Navegación circular |
| `pauseOnHover` | `bool` | `config(true)` | Pausar autoplay al hover |
| `showIndicators` | `bool` | `config(true)` | Mostrar indicadores (dots) |
| `showNavigation` | `bool` | `config(true)` | Mostrar flechas de navegación |
| `numVisible` | `int` | `config(1)` | Slides visibles simultáneamente |
| `gap` | `int` | `config(16)` | Espacio entre slides en px |

## Autoplay

```blade
<x-kore::carousel :autoplay="true" :interval="3000">
    ...
</x-kore::carousel>
```

## Múltiples slides visibles

```blade
<x-kore::carousel :numVisible="3" :gap="24">
    ...
</x-kore::carousel>
```

## Slots

```blade
<x-kore::carousel>
    <x-slot:header>Título</x-slot:header>
    <x-kore::carousel.slide>...</x-kore::carousel.slide>
    <x-slot:footer>Pie de página</x-slot:footer>
</x-kore::carousel>
```
