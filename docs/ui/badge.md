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
