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
| `ariaLabel` | `string\|null` | `config(Carrusel)` | Nombre de la región. Ponlo cuando haya varios carruseles en la misma página |

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

## Accesibilidad

- El contenedor es una `region` con `aria-roledescription="carousel"` y **nombre** (`ariaLabel`). Sin nombre, un lector no lo anuncia como región.
- Cada diapositiva es un `group` con `aria-roledescription="slide"` y su posición —«2 / 4»—, que pone el JavaScript porque el componente no conoce ni su índice ni cuántos hermanos tiene.
- Los indicadores **no son pestañas**: son botones con `aria-current`. Con `numVisible` mayor que uno cada punto lleva a un grupo de diapositivas, así que la relación uno a uno que un `tablist` promete no puede existir.
- **Teclado**: flechas izquierda y derecha se mueven entre diapositivas. Las que se escriben dentro de un campo de texto no se tocan.
- Con `autoplay` aparece un **botón de parar y reanudar** (WCAG 2.2.2), y el avance se detiene además cuando el foco entra en el carrusel. Una pausa pedida a mano manda sobre la del ratón.
- Las diapositivas fuera de la ventana llevan `inert`: sin eso, el tabulador llevaba el foco a un botón que nadie veía.
- El viewport lleva `aria-live="polite"` **solo si no hay autoplay**: con él, un lector estaría anunciando cada diapositiva sin parar.

## Notas de implementación

El ancho de cada diapositiva y la posición del carril se escriben como **estilo en línea**, y no existen en el HTML que emite el servidor: un morph de Livewire los borraba y las diapositivas se encogían al ancho de su contenido. Un `MutationObserver` sobre el carril los reaplica en cuanto faltan —el mismo mecanismo que usan las barras de `<x-kore::splitter>`— y de paso recuenta las diapositivas que el servidor añade después.

Por eso también: no pongas `wire:ignore` en el carrusel. Lo que hace falta es lo contrario, que el servidor pueda cambiar las diapositivas.
