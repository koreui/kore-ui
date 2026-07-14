# Chart Donut

Un donut. Con `inner="0"` es una tarta.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes" title="Reparto">
    <x-kore::chart.donut y="ingresos" />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `y` | `string` | — | La clave del valor |
| `label` | `string` | `null` | El nombre de la serie |
| `inner` | `float` | `0.6` | El agujero, como proporción del radio. `0` = tarta |
| `pad` | `float` | `1` | Separación entre porciones, en grados |

## Detalles que importan

**El donut vive en su propio SVG cuadrado.** Los arcos sí se deformarían con el escalado del gráfico cartesiano, así que no comparte caja con él.

**Una sola porción al 100 % se pinta.** Un arco SVG de 360° es degenerado —el punto inicial coincide con el final— y el navegador no dibujaría nada. El componente lo parte en dos arcos. Es el bug que tiene medio internet.

Los valores negativos, `INF` y `NAN` se ignoran: una porción no puede medir menos que nada.

## Limitaciones

El donut no comparte gráfico con marcas cartesianas: no comparte ni escalas ni caja.
