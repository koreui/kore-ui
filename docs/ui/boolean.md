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

## Accesibilidad

Se anuncia como `role="img"` con el nombre en el idioma de la interfaz. Decía literalmente «true» y «false», en inglés y sin significado.

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| `trueLabel` | `string\|null` | `config(Sí)` | Qué se anuncia cuando el valor es cierto |
| `falseLabel` | `string\|null` | `config(No)` | Y cuando es falso |

```blade
<x-kore::boolean :value="$factura->pagada" trueLabel="Pagada" falseLabel="Pendiente" />
```
