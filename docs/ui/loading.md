# Loading

Indicador de carga con 3 tipos, soporte para overlay y texto.

## Uso básico

```blade
<x-kore::loading />
<x-kore::loading type="dots" />
<x-kore::loading type="pulse" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `type` | `string` | `config(spinner)` | Tipo: `spinner`, `dots`, `pulse` |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg` |
| `text` | `string\|null` | `null` | Texto debajo del indicador |
| `overlay` | `bool` | `false` | Modo overlay (absolute inset-0) |
| `blur` | `bool` | `false` | Backdrop blur en overlay |

## Con texto

```blade
<x-kore::loading text="Cargando datos..." />
```

## Overlay

```blade
<div class="relative">
    <!-- contenido -->
    <x-kore::loading overlay text="Procesando..." />
</div>
```

## Overlay con blur

```blade
<div class="relative">
    <!-- contenido -->
    <x-kore::loading overlay blur />
</div>
```

## Accesibilidad

- El contenedor es `role="status"` con `aria-live="polite"`. Sin `text` propio dice «Cargando» en un `sr-only`: sin texto visible, la animación era la única señal de que algo estaba pasando.
- Con `prefers-reduced-motion` **el spinner se ralentiza** de 1 s a 3 s en vez de apagarse —es la única señal de que algo pasa—, mientras que los puntos y el pulso se apagan del todo.

Los textos salen de `kore-ui.ui.translations.loading`.
