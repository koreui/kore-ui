# Boolean

Indicador visual de valores booleanos con iconos y colores semánticos. Ideal para tablas de datos.

## Uso básico

```blade
<x-kore::boolean :value="true" />
<x-kore::boolean :value="false" />
```

## Props

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `value` | `bool` | `false` | Valor a representar |
| `trueIcon` | `string\|null` | `config(check)` | Icono Lucide para true |
| `falseIcon` | `string\|null` | `config(x)` | Icono Lucide para false |
| `trueColor` | `string\|null` | `config(success)` | Color semántico para true |
| `falseColor` | `string\|null` | `config(destructive)` | Color semántico para false |
| `size` | `string` | `md` | Tamaño: `sm`, `md`, `lg` |

## Colores personalizados

```blade
<x-kore::boolean :value="true" trueColor="primary" />
<x-kore::boolean :value="false" falseColor="warning" />
```

## Iconos personalizados

```blade
<x-kore::boolean :value="true" trueIcon="check-circle" falseIcon="x-circle" />
```
