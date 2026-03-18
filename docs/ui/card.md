# Card

Contenedor con header, body, footer, imágenes, modo colapsable y estado de loading.

## Uso básico

```blade
<x-kore::card title="Mi Card" subtitle="Descripción">
    Contenido de la card.
    <x-slot:footer>Pie de la card</x-slot:footer>
</x-kore::card>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `title` | `string\|null` | `null` | Título |
| `subtitle` | `string\|null` | `null` | Subtítulo |
| `image` | `string\|null` | `null` | URL de imagen |
| `imagePosition` | `string` | `top` | Posición de imagen: `top`, `bottom`, `left` |
| `href` | `string\|null` | `null` | URL — renderiza como enlace |
| `collapsible` | `bool` | `false` | Permite colapsar el contenido |
| `collapsed` | `bool` | `false` | Estado inicial colapsado |
| `loading` | `bool` | `false` | Overlay de loading |
| `bordered` | `bool` | `config(true)` | Borde visible |
| `shadow` | `bool` | `config(true)` | Sombra |
| `padding` | `bool` | `true` | Padding interno |

## Slots

- `default`: Contenido principal
- `header`: Header personalizado
- `footer`: Pie de la card
- `action`: Acción en el header (esquina derecha)

## Collapsible

```blade
<x-kore::card title="Sección" collapsible>
    Contenido colapsable con animación CSS grid.
</x-kore::card>
```

## Con imagen

```blade
<x-kore::card image="/photo.jpg" title="Foto">
    Descripción de la imagen.
</x-kore::card>
```
