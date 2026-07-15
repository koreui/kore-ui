# Chart Bar

Barras. Con el mismo `stack` se apilan; sin `stack`, se agrupan una al lado de otra.

## Uso básico

```blade
{{-- agrupadas --}}
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.bar y="ingresos" label="Ingresos" />
    <x-kore::chart.bar y="gastos"   label="Gastos" />
</x-kore::chart>

{{-- apiladas --}}
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.bar y="web"    stack="canal" />
    <x-kore::chart.bar y="tienda" stack="canal" />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `y` | `string` | — | La clave del valor en cada fila |
| `label` | `string` | `null` | El nombre de la serie |
| `color` | `string` | `null` | Un token o un color CSS |
| `stack` | `string` | `null` | Las barras con el mismo nombre se apilan |
| `show` | `bool` | `true` | `:show="false"` la oculta **pero le reserva su color**. Ver abajo |

## Por qué una barra siempre llega al cero

La **longitud** de la barra *es* el valor. Una barra que empieza en 40 miente sobre la proporción entre las barras. Por eso, en cuanto hay una barra, el eje Y incluye el cero — aunque el resto de series no lo necesiten.

## Sólo se redondea la punta

En una pila, **sólo el último tramo lleva el redondeo**. Si cada tramo llevara el suyo, la columna se vería como una torre de piezas sueltas en vez de como una barra partida en tramos.

Y la punta es **el último tramo con valor**, no el último que declaraste. Si a un mes le falta la serie de arriba, la punta pasa a ser la de debajo:

```php
['mes' => 'Mar', 'web' => 1100, 'tienda' => 700, 'movil' => null],   // la punta es «tienda»
```

Un `0` no cuenta como punta: dibuja un tramo de altura sub-píxel, y redondearlo dejaría cuadrado el que sí se ve. Una barra suelta siempre es su propia punta, y una barra negativa la redondea **abajo**, que es hacia donde crece.

## Las barras son HTML, no SVG

Un `<rect rx="4">` dentro del SVG tendría las esquinas **elípticas**, porque el gráfico se estira horizontalmente y el servidor no conoce la escala en píxeles para compensarlo. Un `<div>` con `border-radius` se clampa solo, tiene `:hover` nativo y se imprime bien.

## Barras horizontales

`orientation="horizontal"` **en el gráfico** (no en la barra) transpone el dibujo: la categoría baja por la izquierda y el valor corre por abajo. **El dato no cambia** — `x` sigue siendo la categoría e `y` el valor; sólo gira la presentación. Es la misma geometría de `layoutBars()`, con los ejes intercambiados.

```blade
<x-kore::chart :data="$departamentos" x="depto" orientation="horizontal">
    <x-kore::chart.bar y="tickets" />
</x-kore::chart>
```

Su razón de ser son las **etiquetas de categoría largas**: «Atención al cliente» cabe a la izquierda tal cual, mientras que en un eje X vertical se pisaría o habría que rotarla. La canaleta se acota a 22ch y lo que se pase se corta con puntos suspensivos.

Funciona igual con **agrupadas** y **apiladas** (mismo `stack`), y con **valores negativos** (crecen a la izquierda del cero). La punta que se redondea es el borde **derecho** (o el izquierdo, si es negativa).

Qué **no** hace, y es a propósito:

- **Sólo transpone barras.** Una línea o un área tendría que invertir su trazo entero, y un donut o un gauge no tiene ejes que transponer; se lanza una excepción si los mezclas.
- **No lleva tooltip flotante ni zoom.** Es barras y CSS: el resalte al pasar el ratón es `:hover` puro, y el valor se lee del eje y la rejilla — como en un gráfico de barras impreso.
- **El eje del valor pide menos ticks que en vertical.** Apilados no se pisan; tumbados sí, así que un eje `0…1.500` sale con cuatro marcas y no con ocho.

## Limitaciones

**No se pueden apilar valores positivos y negativos en la misma pila.** El componente lanza una excepción en vez de dibujar algo que se lee mal.
