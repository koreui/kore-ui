# Eje temporal

Cada punto cae **donde le toca en el calendario**, no donde le toca en el array. Si el sensor estuvo tres días caído, se ven tres días de hueco.

## Uso

Pásale objetos `DateTime` o `Carbon`. La escala se detecta sola.

```blade
<x-kore::chart :data="$lecturas" x="medido_en">
    <x-kore::chart.line y="temperatura" curve="monotone" />
    <x-kore::chart.axis-x />
    <x-kore::chart.tooltip />
</x-kore::chart>
```

```php
$lecturas = Lectura::orderBy('medido_en')->get();   // `medido_en` es un cast a datetime
```

## Por qué esto importa, y no es una mejora estética

**Es una corrección de honestidad.**

Antes había que pre-formatear la fecha en PHP y pasarla como texto. El gráfico la trataba como una **categoría**, y una categoría se coloca por su **ordinal en el array**:

```php
// Un sensor que se cayó del 3 al 5 de febrero. Esas tres filas NO EXISTEN.
[
    ['dia' => '1 feb', 'temp' => 21.4],
    ['dia' => '2 feb', 'temp' => 22.1],
    ['dia' => '6 feb', 'temp' => 19.8],   // ← cuatro días después
]
```

Con categorías, esos tres puntos se dibujan **equiespaciados**: el 2 y el 6 de febrero salen igual de separados que el 1 y el 2. La caída del sensor desaparece. El gráfico se ve perfectamente bien y **miente**.

Con fechas de verdad, el 2 de febrero cae en el 20 % y el 6 en el 100 %. El hueco está ahí porque el hueco existió.

> **Si le pides `scale="time"` y le das texto, salta una excepción.** No adivina: con la fecha ya formateada, lo único que puede hacer es colocar por orden — y eso es exactamente lo que hay que impedir.

## Los ticks caen en el calendario, no cada N filas

Un eje de categorías adelgaza las etiquetas **saltando de N en N**. Con 90 días y 12 etiquetas, eso da los días 1, 9, 17, 25… — que es exactamente el «1.224» que el eje Y se enorgullece de haber eliminado.

Un eje temporal elige **fronteras de calendario**: cada hora, cada 6 horas, cada día, cada semana, cada trimestre. La tabla es la de `d3-time`, y da **exactamente los mismos ticks que d3** (hay un test de paridad contra un fixture generado con `d3-time` de verdad).

Dos detalles de esa tabla que parecen caprichos y no lo son:

- **No existe «cada 4 horas» ni «cada 10 minutos».** No son divisores decentes de 24 ni de 60: los ticks se descuadrarían al día siguiente. La pareja **(unidad, paso) se elige junta**, no «primero la unidad y luego a ver el paso» — que es el fallo de Chart.js, que deja el paso en 1 y para un eje de un año genera **365 ticks de un día** y luego los esconde.
- **Por encima del año se vuelve a `1/2/5 × 10ⁿ`**, sobre años. De ahí salen los ejes por décadas y por siglos, y sale gratis: es el mismo algoritmo del eje Y.

## La segunda línea

En un eje de días, el día 1 imprime «feb» y el resto imprime el número. Cada tick decide solo, sin estado.

Pero eso **falla en el caso más común de todos**: un eje del 10 al 20 de enero no dice en ninguna parte de qué mes habla, porque ningún tick cae en un día 1. Un eje que pone «10 11 12 13 …» y nada más no es un eje: es una lista de números. (Es un agujero real de d3.)

Por eso los ticks llevan una **segunda línea de contexto** — el mes en un eje de días, el día en uno de horas, el año en uno de meses. Se imprime en el primer tick y **cada vez que cambia**, nunca más: repetir «feb» debajo de los catorce días de febrero sería ruido, no información.

## El cambio de hora

`DateTimeImmutable` hace aritmética **de calendario**: el 29 de marzo de 2026 en Madrid solo tiene 23 horas, y sumarle un día te deja en el 30 a medianoche — no a la una.

d3 no puede hacer eso, porque en JavaScript una fecha **es** un número de milisegundos: sumar un día son 86.400.000 ms, y en el cambio de hora eso te deja a las 23:00 o a la 01:00. Por eso `d3-time` vuelve a truncar después de cada salto. Es un parche a una limitación que PHP no tiene.

**Es la única parte del módulo donde PHP no es un mal menor: es estrictamente mejor.** Y de propina, la zona horaria ya está en el servidor, así que no hace falta un adaptador de fechas — que en Chart.js pesa tanto que hace que su *tree-shaking* salga **más caro que el bundle completo**.

> La semana empieza en **lunes** (ISO-8601). d3 la empieza en domingo. Es una decisión, no un descuido.

## La zona horaria no es cosmética

Un pedido de las **23:30 en Madrid** es de las **22:30 en UTC** — o sea, de **otro día**. Un gráfico diario que lea las fechas en UTC pone ese pedido en la barra de ayer.

```blade
<x-kore::chart.axis-x timezone="Europe/Madrid" />
```

Sin `timezone`, se respeta la que traiga el dato. Eloquent las entrega en la zona de la aplicación, así que en el caso normal ya viene bien.

## Barras en un eje de fechas

En una escala de bandas, el ancho de una barra es la banda. En una escala continua **no hay bandas**: los puntos caen donde caen.

El ancho sale del **hueco mínimo** entre dos fechas consecutivas, menos el padding. No del hueco medio: con el medio, dos lecturas más juntas que la media producirían barras **solapadas** — y una barra que tapa a otra no es un gráfico apretado, es un dato escondido.

## El mismo intervalo sirve para agrupar la consulta

Que las escalas vivan en el servidor tiene una consecuencia casi inevitable: **el intervalo que decide los ticks es el mismo con el que hay que agrupar el query**. Es el `$__interval` de Grafana, en Eloquent — y no lo tiene nadie en el ecosistema Laravel.

```php
use KoreUi\Charts\Time\TimeTicks;

$paso = TimeTicks::interval($desde, $hasta, count: 8);   // → «1 week»

Order::selectRaw('DATE_TRUNC(?, created_at) AS bucket, SUM(total)', [$paso->unit()])
     ->whereBetween('created_at', [$desde, $hasta])
     ->groupBy('bucket')
     ->get();
```

`$paso->unit()` da `'day'`, `'week'`, `'month'`… y `$paso->toDateInterval()` un `DateInterval` de PHP.

## Props

| Prop | Tipo | Por defecto | Qué hace |
|---|---|---|---|
| `scale` | string | `auto` | `auto` \| `band` \| `time` \| `linear` |
| `timezone` | string | la del dato | Con qué zona se lee la fecha |
| `ticks` | int | `6` | Cuántos ticks buscar en una escala **continua**. Es una pista, no un contrato |
| `max-labels` | int | `12` | Tope de etiquetas en una escala de **bandas** |
| `show` | bool | `true` | Apaga el eje |

**`auto` nunca promociona a `linear` por su cuenta.** Unos años escritos como enteros (2022, 2023, 2024) son **categorías**, no una recta numérica: colocarlos en una escala lineal le cambiaría el gráfico a quien no ha pedido nada. `linear` hay que escribirlo.

## Cuántos ticks pedir

`ticks` significa cosas distintas en cada escala, y confundirlas sale caro:

- En una **banda** es un **tope**: hay N categorías y se pintan como mucho `max-labels`, saltando el resto.
- En una **continua** es un **objetivo**: no hay categorías que saltar, hay ticks que elegir. Pedir doce para un rango de una semana da uno **cada doce horas**, y se pisan unos con otros.

Por eso el defecto de una escala continua es más bajo (6). Cuando las etiquetas no caben, la respuesta de un gráfico que no puede medir texto es **pedir menos ticks**, nunca truncarlas ni rotarlas.

En un contenedor estrecho (por debajo de 26rem), si hay siete o más etiquetas se esconde **una de cada dos** — nunca la que lleva la línea de contexto, que es la que sitúa el eje. Es una *container query*: un gráfico metido en un sidebar estrecho adelgaza igual aunque la pantalla sea de escritorio.

## Lo que sigue sin haber

**Zoom, pan y streaming** — el eje continuo es el prerrequisito de los tres, pero todavía no están.

Y **una fila sin fecha es un hueco**: se descarta del trazo, no se coloca en el origen. Un punto sin fecha no es un dato en el 1 de enero.
