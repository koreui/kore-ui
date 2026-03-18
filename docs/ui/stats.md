# Stats

Tarjeta de estadística con valor numérico animado, indicador de tendencia e icono.

## Uso básico

```blade
<x-kore::stats label="Total Users" :value="12450" icon="users" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `label` | `string\|null` | `null` | Etiqueta descriptiva |
| `value` | `int\|float` | `null` | Valor numérico |
| `previousValue` | `int\|float\|null` | `null` | Valor anterior para calcular tendencia |
| `icon` | `string\|null` | `null` | Icono Lucide |
| `href` | `string\|null` | `null` | URL (convierte en enlace) |
| `trend` | `string` | `auto` | Tendencia: `auto`, `up`, `down`, `none` |
| `animated` | `bool` | `true` | Animación de conteo al entrar al viewport |
| `variant` | `string` | `default` | Variante: `default`, `compact` |
| `color` | `string` | `primary` | Color del icono: `primary`, `success`, `warning`, `destructive`, `info` |

## Con tendencia

```blade
{{-- Tendencia automática basada en previousValue --}}
<x-kore::stats label="Users" :value="1250" :previousValue="1000" icon="users" />

{{-- Tendencia forzada --}}
<x-kore::stats label="Score" :value="85" trend="up" />
```

Si `trend="auto"` y se proporciona `previousValue`, el porcentaje de cambio se calcula automáticamente.

## Variante compact

```blade
<x-kore::stats label="Users" :value="2450" icon="users" variant="compact" />
```

Layout condensado con icono, label y valor en una sola fila.

## Como enlace

```blade
<x-kore::stats label="View Reports" :value="156" icon="bar-chart-3" href="/reports" />
```

Renderiza como `<a>` con efecto hover.

## Sin animación

```blade
<x-kore::stats label="Total" :value="9999" :animated="false" />
```

## Eventos

La animación de conteo usa `IntersectionObserver` para iniciar solo cuando el elemento es visible en el viewport.

## Configuración

En `config/kore-ui.php`:

```php
'stats' => [
    'variant' => 'default',
    'animated' => true,
],
```
