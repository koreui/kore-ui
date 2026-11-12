# Tokens de diseño

Todo el aspecto de la librería sale de custom properties con prefijo `--kore-`, declaradas en `resources/css/kore-theme.css`. Cambiar el aspecto es redefinirlas en tu CSS, después de importar el tema:

```css
@import 'tailwindcss';
@import '../../vendor/kore-ui/kore-ui/resources/css/kore-theme.css';

:root {
    --kore-radius-md: 0.25rem;      /* controles más cuadrados */
    --kore-duration-base: 240ms;    /* paneles más lentos */
}

.dark {
    --kore-surface: oklch(0.20 0.01 260);
}
```

Los colores semánticos (`--kore-primary`, `--kore-success`…) se documentan en [getting-started.md](getting-started.md). Aquí están los que añade la 3.0.

## Superficies y campos

| Token | Para qué |
|---|---|
| `--kore-bg` | Fondo de página. Ya no es blanco puro: una superficie tiene que poder separarse de él. |
| `--kore-surface` | Tarjetas, tablas, paneles flotantes, modales. |
| `--kore-field` | Fondo de un campo. En claro es blanco; en oscuro va más oscuro que la superficie. |
| `--kore-border` | Borde de superficies y separadores. |
| `--kore-input` | Borde de un control. Un punto más oscuro que `--kore-border`. |
| `--kore-input-hover` | El mismo borde al pasar el puntero. |

## Elevación

Cinco niveles. Cada sombra son dos capas: una corta que pega el elemento a su superficie y una larga que dice a qué altura está.

| Token | Clase | Dónde se usa |
|---|---|---|
| `--kore-shadow-xs` | `shadow-kore-xs` | Campos y botones con borde |
| `--kore-shadow-sm` | `shadow-kore-sm` | Tarjetas, métricas |
| `--kore-shadow-md` | `shadow-kore-md` | Tarjeta enlazada al pasar, tooltip, elemento arrastrado |
| `--kore-shadow-lg` | `shadow-kore-lg` | Menús, lista de un select, calendario, toast |
| `--kore-shadow-xl` | `shadow-kore-xl` | Modal, drawer, spotlight |
| `--kore-shadow-solid` | `shadow-kore-solid` | Botón sólido: brillo superior y sombra corta |

`--kore-shadow-tint` es el color del que salen todas en el tema claro.

## Radios

| Token | Valor | Dónde |
|---|---|---|
| `--kore-radius-sm` | 4 px | Casillas, filas de menú, marcas pequeñas |
| `--kore-radius-md` | 7 px | Controles: campos y botones |
| `--kore-radius-lg` | 10 px | Tarjetas, tablas, paneles flotantes |
| `--kore-radius-xl` | 14 px | Modal y hoja inferior |

## Foco

| Token | Valor | |
|---|---|---|
| `--kore-ring` | color | Color del anillo y del borde de un campo enfocado |
| `--kore-ring-width` | 2 px | Grosor del anillo |
| `--kore-ring-offset` | 2 px | Separación del elemento |
| `--kore-ring-halo` | color | Halo diluido alrededor de un campo enfocado |
| `--kore-ring-halo-destructive` | color | El mismo halo en un campo con error |

## Altura de los controles

Un campo, un select y un botón del mismo tamaño miden lo mismo.

| Token | Valor |
|---|---|
| `--kore-control-sm` | 30 px |
| `--kore-control-md` | 38 px |
| `--kore-control-lg` | 46 px |

## Movimiento

Ver [motion.md](motion.md).

## Utilidades

Las plantillas de la librería nombran estas utilidades en vez de repetir sus reglas. Están disponibles también en tu aplicación, para que un componente propio se vea igual que los de la librería.

| Utilidad | Qué hace |
|---|---|
| `kore-control` | Aspecto de un campo: fondo, borde, sombra, halo de foco, error (`aria-invalid`, `data-invalid` o `.kore-invalid`) y deshabilitado. |
| `kore-panel` | Panel flotante: superficie, borde, radio y sombra `lg`. |
| `kore-focus` | Anillo de foco, solo con teclado (`:focus-visible`). |
| `kore-focus-inset` | El mismo anillo por dentro, para lo que va pegado a un borde. |
| `kore-focus-within` | El anillo cuando el foco lo tiene un hijo. |
| `kore-transition` · `-base` · `-slow` | Transición de color, borde, sombra, opacidad y transformación con la duración de cada nivel. |
| `kore-duration-fast` · `-base` · `-slow` | Solo la duración y la curva, para acompañar a una `transition-[propiedad]`. |
| `kore-pop` · `kore-fade` · `kore-slide-down` · `kore-drawer` | Entrada y salida de un elemento con `x-show`, sin `x-transition`. |
| `kore-press` | El elemento cede un poco al pulsarlo. |
| `kore-option` | Fila de un menú o de una lista de opciones. |
| `kore-tool` | Botón de barra de herramientas, de la altura de un control pequeño. |
| `kore-checkmark` · `kore-radiomark` | Casilla y radio nativos, redibujados. |

```blade
<input class="kore-control px-3 py-2 text-sm" />

<div x-show="abierto" class="kore-panel kore-pop p-2">…</div>

<button class="kore-focus kore-press kore-transition rounded-kore-md px-3 py-2">…</button>
```

## Volver al aspecto de la 2.x

```css
:root {
    --kore-bg: oklch(1 0 0);
    --kore-fg: oklch(0.15 0 0);
    --kore-muted: oklch(0.97 0 0);
    --kore-muted-fg: oklch(0.55 0 0);
    --kore-border: oklch(0.92 0 0);
    --kore-input: oklch(0.92 0 0);
    --kore-input-hover: oklch(0.92 0 0);
    --kore-radius-md: 0.375rem;
    --kore-radius-lg: 0.5rem;
    --kore-radius-xl: 0.75rem;
    --kore-shadow-xs: none;
    --kore-shadow-solid: none;
}
```
