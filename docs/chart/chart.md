# Chart

El contenedor del gráfico: recibe los datos y dentro le pones las capas que quieras pintar.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.line y="ingresos" />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `data` | `array` | `[]` | Las filas. Arrays, objetos o modelos Eloquent |
| `x` | `string` | `null` | La clave del eje X. Sin ella, se usa el índice de la fila |
| `height` | `string` | `16rem` | Longitud CSS. Se ignora si pones `aspect` |
| `aspect` | `string` | `null` | Proporción CSS, p. ej. `16/9`. Alternativa a `height` |
| `title` | `string` | `null` | Título visible sobre el gráfico |
| `ariaLabel` | `string` | `null` | El `<caption>` de la tabla accesible. Si no, se usa el título |
| `id` | `string` | `null` | Id del gráfico. Por defecto, uno determinista por petición |

## Estado vacío

Sin datos —o con una serie que es toda `null`— el gráfico enseña el estado vacío en lugar de intentar dibujar. Nunca divide por cero.

## Limitaciones

El `id` es determinista a propósito. Si le pasas uno aleatorio, el morph de Livewire verá un nodo distinto en cada actualización, lo reemplazará entero y el degradado del área parpadeará.
