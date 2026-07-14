# Chart Tooltip

El tooltip y el crosshair.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.line y="ingresos" />
    <x-kore::chart.tooltip />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `crosshair` | `bool` | `true` | La línea vertical que sigue al puntero |

## Cómo funciona

Los valores del tooltip **llegan ya formateados desde PHP**. El JavaScript no toca un número: no sabe de monedas, ni de locales, ni de separadores. Eso evita tener que mantener el formateo dos veces (en PHP y en JS) y deja el bundle sin una sola línea de `Intl`.

Encontrar el punto bajo el ratón es una **búsqueda binaria** sobre las posiciones X. Con 10.000 puntos son 14 comparaciones.

La X del tooltip se **engancha al dato**, no al ratón: moverse dentro de la misma banda no lo hace temblar. La Y sí sigue al cursor.

Los decimales los deduce cada serie de sus propios valores (el eje, en cambio, los deduce del paso entre ticks). Un sensor que marca 21,4 °C se escribe «21,4», no «21». Y un `null` se escribe «—»: no hay dato, que no es lo mismo que valer cero.

## Limitaciones

**El tooltip es lo único que hace que el gráfico cargue un payload de datos.** Sin él, el HTML no lleva una segunda copia de los valores: a 2.000 puntos, ese payload pesa más que el propio trazo.

Ahora mismo el tooltip solo responde al ratón. Con teclado, los datos están en la tabla accesible.
