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
| `announce` | `bool` | `true` | Anuncia la carga a los lectores de pantalla. A `false` quita el `role="status"` y el indicador queda mudo |

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
- Con `:announce="false"` el contenedor se queda **sin** `role="status"` ni `aria-live`, y tampoco se pinta el «Cargando» oculto: el indicador entero es invisible para un lector de pantalla. Es para meter un `loading` dentro de algo que ya anuncia su propio estado. El DataTable, por ejemplo, tiene su `aria-live` con el recuento de resultados: con los dos, al filtrar se oía «Cargando» y a continuación «Mostrando 1 de 1» —dos avisos para un solo hecho—.

```blade
{{-- El contenedor de fuera ya tiene su propio aria-live --}}
<div aria-live="polite">
    <x-kore::loading :announce="false" />
</div>
```

Los textos salen de `kore-ui.ui.translations.loading`.
