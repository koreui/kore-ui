# Tooltip

Tooltip ligero con CSS puro para posicionamiento y Alpine.js para show/hide.

## Uso básico

```blade
<x-kore::tooltip text="Información adicional">
    <x-kore::button label="Hover me" />
</x-kore::tooltip>
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `text` | `string\|null` | `null` | Texto del tooltip |
| `position` | `string` | `config(top)` | Posición: `top`, `right`, `bottom`, `left` |
| `delay` | `int` | `config(200)` | Delay en ms antes de mostrar |

## Posiciones

```blade
<x-kore::tooltip text="Arriba" position="top">...</x-kore::tooltip>
<x-kore::tooltip text="Abajo" position="bottom">...</x-kore::tooltip>
<x-kore::tooltip text="Izquierda" position="left">...</x-kore::tooltip>
<x-kore::tooltip text="Derecha" position="right">...</x-kore::tooltip>
```

## Accesibilidad

- `role="tooltip"` en el contenedor del texto
- Se muestra con hover y focus
- Se oculta con mouseleave y blur
