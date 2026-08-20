# Badge

Etiqueta/badge para mostrar estados, categorías o contadores.

## Uso básico

```blade
<x-kore::badge label="Nuevo" />
<x-kore::badge label="Activo" color="success" />
<x-kore::badge label="Pendiente" color="warning" variant="outline" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Texto del badge |
| `icon` | `string\|null` | `null` | Icono Lucide |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg` |
| `variant` | `string` | `config(soft)` | Variante: `solid`, `soft`, `outline` |
| `color` | `string` | `primary` | Color: `primary`, `secondary`, `success`, `warning`, `destructive`, `info`, `muted` |
| `dot` | `bool` | `false` | Renderiza solo un punto de color |

## Dot

```blade
<x-kore::badge :dot="true" color="success" />
<x-kore::badge :dot="true" color="destructive" />
```

## Accesibilidad

Las variantes `soft` y `outline` usan el token `text-kore-{color}-text`, no el color base. El color base está pensado para ser un **fondo**: como texto sobre su propio tinte al diez por ciento no llega a AA — medido, `success` daba 3,01 y `info` 3,24.

> La variante `solid` todavía no llega a AA en ninguno de los cuatro colores (`primary` 4,41 · `destructive` 4,39 · `info` 3,42 · `success` 3,17): pinta el color `-fg` sobre el color pleno, y arreglarlo pide mover la paleta base.
