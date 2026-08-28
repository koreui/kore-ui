# Theme Switch

Component for switching between light, dark, and system themes. Three variants in a single anonymous Blade component.

## Basic Usage

```html
{{-- Segmented (default) --}}
<x-kore::theme-switch />

{{-- Toggle --}}
<x-kore::theme-switch variant="toggle" />

{{-- Dropdown --}}
<x-kore::theme-switch variant="dropdown" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `variant` | `string` | `segmented` | Variante visual: `segmented`, `toggle`, `dropdown` |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg` |
| `labels` | `bool` | `false` | Muestra el texto junto al icono |
| `lightLabel` | `string\|null` | `Claro` | Nombre del modo claro |
| `darkLabel` | `string\|null` | `Oscuro` | Nombre del modo oscuro |
| `systemLabel` | `string\|null` | `Sistema` | Nombre del modo sistema |
| `ariaLabel` | `string\|null` | `Tema` | Nombre accesible del control. **No lo usa la variante `toggle`** (ver abajo) |

Los cuatro últimos salen de `kore-ui.ui.translations` cuando no se pasan:
`theme_light`, `theme_dark`, `theme_system` y `theme`.

## Variants

### Segmented

Default variant. Renders a `role="radiogroup"` with three radio buttons for light, system, and dark. Active option gets a raised background with shadow.

```html
<x-kore::theme-switch />

{{-- With labels --}}
<x-kore::theme-switch :labels="true" />
```

### Toggle

Simple light/dark switch without the system option. Uses `role="switch"` with a sliding thumb containing sun/moon icons.

```html
<x-kore::theme-switch variant="toggle" />
```

Note: Toggle alternates between `light` and `dark` only. If the store was on `system`, the first click sets it to whichever mode is opposite to the current resolved theme.

### Dropdown

Compact button that opens a floating menu with all three options. The trigger shows the icon of the current mode.

```html
<x-kore::theme-switch variant="dropdown" />

{{-- Trigger shows label too --}}
<x-kore::theme-switch variant="dropdown" :labels="true" />
```

The dropdown panel is teleported to `<body>` via `x-teleport` to escape `overflow: hidden` containers. Positioned with `position: fixed` and `z-[9999]`.

## Sizes

Three sizes available on all variants:

```html
<x-kore::theme-switch size="sm" />
<x-kore::theme-switch size="md" />
<x-kore::theme-switch size="lg" />
```

| Size | Icon | Text | Padding |
|------|------|------|---------|
| `sm` | `size-4` | `text-xs` | `px-2 py-1` |
| `md` | `size-5` | `text-sm` | `px-3 py-1.5` |
| `lg` | `size-6` | `text-base` | `px-3.5 py-2` |

Toggle variant track sizes match the form toggle component:

| Size | Track | Thumb |
|------|-------|-------|
| `sm` | `h-5 w-9` | `size-3.5` |
| `md` | `h-6 w-11` | `size-4.5` |
| `lg` | `h-7 w-14` | `size-5.5` |

## Textos propios

Los tres nombres de modo se sobrescriben en la etiqueta:

```html
<x-kore::theme-switch :labels="true"
    light-label="Light" dark-label="Dark" system-label="Auto" />
```

Si el cambio es para toda la aplicación, va en `config/kore-ui.php` bajo
`ui.translations` y no hace falta repetirlo en cada instancia.

## Nombre accesible

`ariaLabel` es el nombre del **control entero** —lo que anuncia un lector antes
de leer las opciones—, no el de cada modo:

```html
<x-kore::theme-switch aria-label="Apariencia de la interfaz" />
```

Dónde aterriza, según la variante:

| Variante | Qué nombra `ariaLabel` |
|----------|------------------------|
| `segmented` | El `role="radiogroup"`. Cada botón se sigue nombrando con su `lightLabel` / `systemLabel` / `darkLabel` |
| `dropdown` | El botón disparador **y** el `role="menu"` del panel |
| `toggle` | **Nada.** Ver abajo |

La variante `toggle` es la excepción: su nombre es siempre
`ui.translations.theme_toggle` («Cambiar entre claro y oscuro»), porque describe
una acción y no un selector. `ariaLabel` se filtra ahí de forma explícita, así
que escribirlo no da error ni tiene efecto —para cambiarlo hay que tocar la
configuración—.

## Shared State

All theme switch instances on a page share the same `$store.koreTheme`. Changing one updates all others instantly via Alpine's reactivity:

```html
{{-- These three stay in sync --}}
<x-kore::theme-switch />
<x-kore::theme-switch variant="toggle" />
<x-kore::theme-switch variant="dropdown" />
```

## Accessibility

| Variant | Role | Keyboard |
|---------|------|----------|
| Segmented | `radiogroup` + `radio` buttons | Tab between buttons, Space/Enter to select |
| Toggle | `switch` with `aria-checked` | Space/Enter to toggle |
| Dropdown | `menu` with `menuitem` buttons | Escape closes, Space/Enter selects |

All variants use `focus-visible:ring-2 focus-visible:ring-kore-ring` for focus indication.

## Styling

All variants use semantic tokens exclusively:

- Active: `bg-kore-bg`, `text-kore-fg`, `shadow-sm`
- Inactive: `text-kore-muted-fg`, `hover:text-kore-fg`
- Container: `bg-kore-muted`, `rounded-kore-lg`
- Focus: `ring-kore-ring`
- Dropdown border: `border-kore-border`

Extra classes can be passed via attributes on the segmented and dropdown variants:

```html
<x-kore::theme-switch class="my-custom-class" />
```
