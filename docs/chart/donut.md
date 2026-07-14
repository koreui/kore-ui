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
| `highlight` | `bool` | `true` | Al posarte sobre un arco, enciende también su fila de la leyenda. Es CSS puro |
| `show` | `bool` | `true` | `:show="false"` la oculta **pero le reserva su color**. Ver abajo |

## Detalles que importan

**El donut vive en su propio SVG cuadrado.** Los arcos sí se deformarían con el escalado del gráfico cartesiano, así que no comparte caja con él.

**Una sola porción al 100 % se pinta.** Un arco SVG de 360° es degenerado —el punto inicial coincide con el final— y el navegador no dibujaría nada. El componente lo parte en dos arcos. Es el bug que tiene medio internet.

Los valores negativos, `INF` y `NAN` se ignoran: una porción no puede medir menos que nada.

**Al posarte sobre un arco se enciende su fila de la leyenda, y al revés.** Sin esa relación, para saber qué porción es «Abr» hay que emparejar el color a ojo entre el arco y la leyenda — que es lo que peor funciona, y peor todavía con daltonismo. Está hecho con `:has()` en CSS: **ni una línea de JavaScript**, y el arco y su fila se enlazan por un `data-slice` común.

Hace falta una regla de CSS por índice, porque CSS no sabe comparar dos atributos entre sí. Van hasta la porción 12. Por encima de esa cifra el resaltado simplemente no se activa (no se apaga a medias): un donut con más de doce porciones ya no se lee, y la cola hay que agruparla en «Otros».

## El donut no lleva tooltip

Y no es un olvido. Un tooltip existe para leer un valor que la geometría no te da; aquí la leyenda **ya imprime la etiqueta, el valor y el porcentaje de cada porción, de forma permanente**. Un tooltip repetiría lo que ya está en pantalla, y a cambio metería en el DOM una segunda copia de todos los datos.

Si escribes `<x-kore::chart.tooltip />` dentro de un donut, no se pinta.

Y si necesitas leer las seis cifras una a una, el gráfico que quieres no es un donut: es una barra. Un donut se lee comparando superficies.

## Limitaciones

El donut no comparte gráfico con marcas cartesianas: no comparte ni escalas ni caja.
