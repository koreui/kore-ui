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
| `target` | `string\|null` | `null` | `target` del enlace, p. ej. `_blank`. Se emite tal cual, sin añadir `rel`: para abrir en otra pestaña pon tú `rel="noopener"` |
| `collapsible` | `bool` | `false` | Permite colapsar el contenido |
| `collapsed` | `bool` | `false` | Estado inicial colapsado |
| `loading` | `bool` | `false` | Overlay de loading |
| `bordered` | `bool\|null` | `null` | Borde visible. Ver [aspecto](look.md) |
| `shadow` | `bool\|null` | `null` | Sombra. Ver [aspecto](look.md) |
| `padding` | `bool\|null` | `null` | Relleno interior. Ver [aspecto](look.md) |
| `skeleton` | `bool\|int` | `false` | Silueta mientras no hay datos; el entero elige las líneas del cuerpo. Ver [skeleton](skeleton.md#siluetas-de-componente) |

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

## Card enlazada

Con `href` la card entera se renderiza como `<a>` en vez de `<div>`, y gana el
realce de sombra al pasar el ratón. `target` viaja al enlace:

```blade
<x-kore::card href="https://ejemplo.test/informe" target="_blank" rel="noopener" title="Informe anual">
    Se abre en otra pestaña.
</x-kore::card>
```

El componente **no** añade `rel="noopener"` por su cuenta: si abres en otra
pestaña, escríbelo tú en la etiqueta —el `rel` viaja en el bag de atributos y
llega al `<a>`—.

## Aspecto

`bordered`, `shadow`, `padding` y `compact` se pueden fijar también para toda la
librería desde `config/kore-ui.php`. Ver [aspecto de las superficies](look.md).
