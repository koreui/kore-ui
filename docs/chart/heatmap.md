# Mapa de calor (heatmap)

Una matriz de **columna × fila**, donde el color es el valor. Actividad por hora y día, retención por cohortes, un calendario de commits.

## Uso

```blade
<x-kore::chart :data="$actividad" x="hora">
    <x-kore::chart.heatmap y="sesiones" row="dia" :buckets="6" />
</x-kore::chart>
```

```php
// Formato «largo», el de un GROUP BY: una fila por celda.
$actividad = [
    ['dia' => 'Lun', 'hora' => '09', 'sesiones' => 72],
    ['dia' => 'Lun', 'hora' => '10', 'sesiones' => 68],
    // ...
];
```

Tres canales, no uno: la **columna** es el `x` del gráfico, la **fila** es `row`, y el **valor** es `y`. El orden de columnas y filas es el orden en que aparecen en el dato (ordena por `(fila, columna)` en tu SQL y salen ordenadas).

## El color se cuantiza, no se interpola

El valor cae en uno de N escalones (`:buckets`, entre 3 y 7) y la celda lleva un `data-bucket`; el color lo pone el CSS con la rampa secuencial (`--kore-seq-*`, la de la Fase 1).

**PHP no calcula un solo color.** Así el tema sigue cambiando sin ejecutar JavaScript —el invariante que ordena todo el módulo— y de paso una escala de escalones se lee: un degradado continuo obliga a mirar la leyenda para cada celda.

Un cruce **sin dato** no es un cero: la celda se queda sin color y se ve el fondo. Pintarla del tono más claro diría «poco», y es «nada».

## El hover va por delegación

Un heatmap de 365×7 son **2.555 celdas**. Poner un listener por celda cuesta 30 ms por frame (medido). Así que hay **un solo `pointermove`** en la rejilla, y el manejador lee el `data-*` de la celda que hay debajo del ratón.

El *resalte* de la celda, en cambio, es `:hover` de CSS puro: una pseudoclase sobre 2.555 nodos es barata; 2.555 listeners no.

## Props

| Prop | Tipo | Por defecto | Qué hace |
|---|---|---|---|
| `y` | string | — | La columna del valor (el color) |
| `row` | string | — | La columna de la fila (la columna la da el `x`) |
| `buckets` | int | `5` | Cuántos escalones de color, de 3 a 7 |
| `show` | bool | `true` | Apaga la marca |

## Un heatmap no comparte gráfico

Llena una matriz de celdas, no dibuja una serie sobre unos ejes cartesianos. Como el donut, el gauge o el embudo, vive solo — y si le pones otra marca, **lanza**.
