# Progress

Indicadores de progreso en barra y círculo para mostrar avance de operaciones.

## Progress Bar

### Uso básico

```blade
<x-kore::progress :value="65" />
<x-kore::progress :value="80" color="success" showValue />
<x-kore::progress indeterminate />
```

### Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `value` | `int\|float` | `0` | Valor actual |
| `max` | `int\|float` | `100` | Valor máximo |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg` |
| `color` | `string` | `primary` | Color: `primary`, `success`, `warning`, `destructive`, `info`, `auto` |
| `showValue` | `bool` | `false` | Muestra el porcentaje |
| `indeterminate` | `bool` | `false` | Animación de progreso indeterminado |
| `striped` | `bool` | `false` | Patrón de rayas |
| `animated` | `bool` | `false` | Anima las rayas (requiere `striped`) |
| `label` | `string\|null` | `null` | Etiqueta descriptiva |

### Con etiqueta y valor

```blade
<x-kore::progress :value="45" label="Subiendo archivo..." showValue />
```

### Color automático

```blade
{{-- Cambia de color según el porcentaje: destructive → warning → success --}}
<x-kore::progress :value="30" color="auto" />
```

### Rayas animadas

```blade
<x-kore::progress :value="70" striped animated color="info" />
```

### Indeterminado

```blade
<x-kore::progress indeterminate label="Procesando..." />
```

---

## Progress Circle

### Uso básico

```blade
<x-kore::progress.circle :value="75" />
<x-kore::progress.circle :value="100" color="success" showValue />
```

### Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `value` | `int\|float` | `0` | Valor actual (0–100) |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg`, `xl` |
| `color` | `string` | `primary` | Color: `primary`, `success`, `warning`, `destructive`, `info` |
| `showValue` | `bool` | `true` | Muestra el porcentaje en el centro |
| `strokeWidth` | `int` | `8` | Grosor del trazo SVG (en unidades del viewBox 100x100) |

### Tamaños

```blade
<x-kore::progress.circle :value="50" size="sm" />
<x-kore::progress.circle :value="50" size="md" />
<x-kore::progress.circle :value="50" size="lg" />
<x-kore::progress.circle :value="50" size="xl" showValue />
```

### Grosor personalizado

```blade
<x-kore::progress.circle :value="60" :strokeWidth="8" size="lg" showValue />
```
