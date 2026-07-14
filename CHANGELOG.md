# Changelog

Todos los cambios notables en este proyecto se documentan en este archivo.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y el proyecto usa [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.6.0] — 2026-07-14

**Gráficos.** Barras, líneas, áreas y donut, **sin ninguna librería de JavaScript**: la geometría se calcula en PHP y el servidor devuelve el `<svg>` ya dibujado.

### Added

- **`<x-kore::chart>`** y sus marcas: `chart.line`, `chart.area`, `chart.bar`, `chart.donut`, `chart.axis-x`, `chart.axis-y`, `chart.legend` y `chart.tooltip`. Ver [docs/chart/getting-started.md](docs/chart/getting-started.md).
- **`Kore\Charts\`**: el motor de geometría, en PHP puro y sin depender de Blade ni de JavaScript. Sirve igual para un PDF, un email o un export.
- **Paleta categórica `--kore-chart-1` … `--kore-chart-8`**, en claro y en oscuro.

- **Eje X temporal.** Ver [docs/chart/time-axis.md](docs/chart/time-axis.md).

  Pásale objetos `DateTime` o `Carbon` y la escala se detecta sola. **No es una feature estética: es una corrección de honestidad.** Hasta ahora había que pre-formatear la fecha en PHP, el gráfico la trataba como una categoría, y una categoría se coloca por su **ordinal en el array** — así que un sensor que estuvo tres días caído se dibujaba con sus lecturas pegadas una a otra. La caída desaparecía. El gráfico se veía perfectamente bien y mentía.

  Y el eje X tenía el defecto que el eje Y ya no tiene: adelgazaba las etiquetas **saltando de N en N**, así que 90 días con 12 etiquetas daban los días 1, 9, 17, 25… — que es exactamente el «1.224» del que presume de haberse librado. Ahora los ticks caen en **fronteras de calendario**, con la tabla de `d3-time`, y hay un test de paridad contra un fixture generado con `d3-time` de verdad: **dan exactamente los mismos ticks que d3**.

  **Aquí PHP no es un mal menor: es estrictamente mejor que JavaScript.** `DateTimeImmutable` hace aritmética de calendario, así que un día de 23 o de 25 horas le sale bien por construcción. d3 tiene que volver a truncar después de cada salto, porque en JavaScript una fecha *es* un número de milisegundos y sumarle un día son 86.400.000 ms — que en el cambio de hora te dejan a las 23:00. Y la zona horaria ya está en el servidor, así que no hace falta un adaptador de fechas: el de Chart.js pesa tanto que hace que su *tree-shaking* salga **más caro que su bundle completo**.

  De propina, y casi inevitable: **el intervalo que elige los ticks es el mismo con el que hay que agrupar la consulta**. Es el `$__interval` de Grafana, en Eloquent, y no lo tiene nadie en Laravel:

  ```php
  $paso = TimeTicks::interval($desde, $hasta, count: 8);   // → «1 week»

  Order::selectRaw('DATE_TRUNC(?, created_at) AS bucket, SUM(total)', [$paso->unit()])
       ->groupBy('bucket');
  ```

- **Eje X lineal** (`scale="linear"`), para valores continuos. `auto` **nunca** lo elige por su cuenta: unos años escritos como enteros son categorías, no una recta numérica.

- **Rampas de color secuencial (`--kore-seq-1…7`) y ordinal (`--kore-ord-1…7`)**, en claro y en oscuro. La secuencial codifica una **magnitud** (el valor de una celda); la ordinal solo codifica un **orden** (la etapa de un embudo, donde el valor ya lo dice la geometría). El color se **cuantiza**, jamás se interpola en PHP: el servidor reparte el valor en escalones y emite el número; el color lo pone el CSS. Así el gráfico sigue repintándose solo al cambiar de tema.

- **Zoom, pan y slider de contexto** — todo resuelto en el servidor. Ver [docs/chart/zoom.md](docs/chart/zoom.md).

  **El cliente manda dos números.** La ventana son dos porcentajes del dominio completo —no dos fechas—, y eso es lo que hace que el zoom no cueste ni una línea de matemática de escalas en el cliente: componer un zoom sobre otro es una regla de tres, y recortar el espacio 0–100 a un tramo es un **remapeo afín** que funciona igual con categorías, con fechas y con números. El servidor invierte el dominio (con `LinearScale::invert()`, que llevaba escrita desde el primer día **sin que la usara nadie**), elige los ticks nuevos, reescala el eje Y y devuelve el `<path>`. Livewire lo morphea.

  Lo más visible: **al ampliar, el eje temporal cambia de unidad solo.** Un año dice trimestres; ampliada una semana, el mismo eje dice días. Un zoom en el cliente tendría que recalcular esos ticks — o sea, portar `TimeTicks`, `TimeInterval` y `TimeFormat` a JavaScript. Todo el JS del zoom son **~60 líneas y 0,7 kB gzip**.

  **El estado vive en el componente Livewire**, no en Alpine. Sale gratis que sobreviva al morph sin ningún hook, que se comparta por URL con `#[Url]` y que se testee con `Livewire::test()`.

  **Y el eje Y se reescala sobre lo que se ve** (el `filterMode: 'filter'` de ECharts): ampliar una semana de un año y dejar el eje llegando al máximo anual deja el gráfico aplastado contra el suelo. Las filas de fuera no se borran —el trazo tiene que seguir *saliendo* por el borde, no cortarse contra él—: el recorte es visual, con `clip-path`.

  Los controles son **`<button>` de verdad**, y la ventana del contexto se desplaza con las flechas: ni ECharts, ni uPlot, ni Highcharts tienen un zoom que se pueda usar con el teclado.

  **No te puedes quedar atrapado.** Hay un **suelo** para la ventana —dos separaciones medias, que calcula el servidor porque es el único que sabe cuántas filas hay—, así que no se puede ampliar hasta que no quede nada. Y como el suelo *no* garantiza que haya datos (en una serie con un hueco puedes ampliar dentro del hueco, y ahí no hay nada), **el estado vacío conserva los controles**: dice «No hay datos en este tramo» y mantiene el botón de restablecer. Un gráfico puede quedarse sin datos que enseñar; lo que no puede es quedarse sin salida.

  ⚠️ **Sin rueda ni pinch, y va escrito en la doc.** Una rueda dispara ~50 eventos/s y cada uno cambia los ticks: hacerlo bien exige portar `Ticks`, `Scales`, `Path` y `Format` a JS y mantener dos implementaciones de la geometría idénticas para siempre. Es la deuda que esta arquitectura se eligió para no contraer.

- **Datos en vivo** con `<x-kore::chart.stream>`. Ver [docs/chart/streaming.md](docs/chart/streaming.md).

  **El morph de Livewire ya ERA la actualización**: cambia el atributo `d` del `<path>` sin recrear el nodo. No hay instancia de JavaScript que proteger, así que no hay nada que parpadee — que es el issue **#20103 de Filament**, abierto porque cada refresco destruye y recrea una instancia de Chart.js. Lo que había que construir no era refrescar: era **saber cuándo no hacerlo**.

  Un `wire:poll` a secas refresca siempre. El gráfico se para solo en los tres momentos en que eso es hostil: **mientras lees un tooltip** (el número que estás mirando no puede cambiar bajo el cursor), **con la pestaña oculta** (diez pestañas serían diez renders por segundo en tu servidor, para nadie) y **con el zoom puesto** (has ido a mirar algo concreto; no se te mueve el suelo).

  La ventana deslizante es un `array_slice`: ni ring buffer, ni motor. Y todo el JavaScript son ~25 líneas.

  **Si el gráfico tiene trazo, no se anima nada.** Animar la línea exigiría `transition: d`, y está medido en los tres motores con Playwright: Firefox interpola, **WebKit ni siquiera lo soporta** y Chromium dice soportarlo pero da un salto seco. Y hay una razón mejor: en una ventana deslizante, interpolar `d` lleva el punto *i* hasta el valor del punto *i+1* — la onda **tiembla en el sitio** en vez de desplazarse.

  Así que el trazo **salta**. Y todo lo que se mueva despacio mientras el trazo salta **se despega de él**: medido, con los puntos animados el peor se iba a **8,36 % del área** de la curva sobre la que se supone que está — unos 24 px. O se anima todo, o no se anima nada; y como el trazo no puede, no se anima nada.

  Las transiciones sólo se encienden cuando **no hay línea ni área** (un gráfico de barras en vivo: ahí no hay trazo del que despegarse), y sólo sobre lo **vertical** — lo horizontal salta, porque las etiquetas del eje X también saltan. Las barras llevan `wire:key` cuando hay stream: sin ella, el morph reutiliza la barra *i* para el dato *i+1* y la barra crece en el sitio en vez de que la de al lado se desplace.

  ⚠️ **El techo va escrito, medido y con excepción.** Un refresco es un round-trip completo de Livewire, y **medido: 44 kB de HTML — 5,1 kB gzip — por refresco** con 40 puntos y 2 series. Son N renders para N clientes. `every` **lanza** por debajo de 500 ms. El techo honesto es **1 Hz con ≤ 200 puntos**; a 10 Hz no aguanta ninguna arquitectura que dibuje en el servidor, ni ésta ni ninguna.

- **`max-gap` en `<x-kore::chart.line>` y `<x-kore::chart.area>`**: el trazo **se parte** cuando entre dos puntos consecutivos pasa más de lo que debería.

  Un `null` explícito ya partía la línea desde el primer día. Lo que **no** partía nada era una fila que sencillamente **no está**: ahí la línea cruzaba el hueco dibujando una curva suave por encima de un rato en el que no hubo dato — y con `curve="monotone"`, un swoop que *parece* dato. **Es la misma mentira que arregló el eje temporal, un piso más arriba**: entonces el hueco desaparecía porque los puntos se colocaban por su ordinal; ahora el hueco se ve, pero el trazo lo tapa.

  Acepta `«30s»`, `«5m»`, `«2h»`, `«7d»`… o un número. En un eje de categorías **lanza**: mide una distancia entre dos puntos, y entre dos categorías no hay ninguna.

- **`min` y `max` en `<x-kore::chart.axis-y>`**, para fijar el dominio. `Domain::fromSeries()` los aceptaba desde el primer día —y los trata como un contrato, no los redondea por debajo de lo que pides— pero **nadie se los pasaba nunca**. En un gráfico en vivo dejan de ser un lujo: un eje que se reescala cada dos segundos porque el dato subió un punto es ilegible.

- **Cascada (`<x-kore::chart.waterfall>`)** — el puente entre un valor inicial y uno final. Ver [docs/chart/waterfall.md](docs/chart/waterfall.md).

  **Es un apilado de una sola serie con la base moviéndose por fila**: la barra flotante ya la calculaba `layoutBars()` para las pilas, así que no hay geometría nueva. Cada etapa es un salto que sube (verde) o baja (rojo); una fila marcada como `total` es un descansillo que va del cero al acumulado, en neutro. **Los totales se calculan solos si los dejas vacíos** — no hay que repetir la suma en el dato. El color codifica **polaridad, no identidad**: es el único sitio donde una serie usa los tokens semánticos, y es legítimo (`--kore-success` significa «esto suma»).

- **Gauge (`<x-kore::chart.gauge>`)** — un número, contra un objetivo, y con rangos de color. Ver [docs/chart/gauge.md](docs/chart/gauge.md).

  **Reutiliza el donut**: vive en su mismo SVG cuadrado con escalado uniforme, y la trigonometría del arco ya estaba en `Arc` (un método nuevo, `Arc::open()`, para trazar un arco abierto en vez de rellenar un anillo). El número y el arco se pintan con el color de la banda en la que cae el valor —verde, ámbar, rojo—, y unos pellizcos marcan dónde empieza cada una. El dominio no tiene por qué ser 0–100, el arco no tiene por qué ser de 270° (`sweep="180"` = semicírculo), y el número no pierde los decimales (un SLA de 99,2 no sale «99»).

  ⚠️ **Sin rangos de color, un gauge es un stat tile con un anillo decorativo**, y la documentación te lo dice: lo que justifica el arco es el contexto de color, no el arco.

- **Embudo (`<x-kore::chart.funnel>`)** — cuánta gente sobrevive cada paso de un proceso. Ver [docs/chart/funnel.md](docs/chart/funnel.md).

  Cada etapa es un trapecio (un `<div>` recortado con `clip-path`) que se estrecha hasta la siguiente: el estrechamiento **es** la caída, y al lado van la conversión (cuánto queda del primero) y la caída de ese paso (cuánto se pierde ahí, en rojo). El color sale de la rampa **ordinal** —`--kore-ord-*`, la que se hizo en la Fase 1—, no de la categórica: las etapas van en secuencia, y ahí el color sólo dice «vas por aquí»; el peso de la información lo lleva el ancho del trapecio. El orden de las filas es el orden del embudo, y no se reordena. Al pasar el ratón sobre una etapa se enciende el par (trapecio + fila) y se apaga el resto — CSS puro, como el donut, y se apaga con `:highlight="false"`.

- **Mapa de calor (`<x-kore::chart.heatmap>`)** — una matriz de columna × fila donde el color es el valor. Ver [docs/chart/heatmap.md](docs/chart/heatmap.md).

  Tres canales (la columna es el `x`, la fila es `row`, el valor es `y`), en formato «largo» —el de un `GROUP BY`—. **El color se cuantiza, no se interpola**: el valor cae en uno de N escalones (`:buckets`, 3–7), la celda lleva un `data-bucket` y el color lo pone el CSS con la rampa secuencial de la Fase 1 — así el tema sigue cambiando sin JavaScript, y una escala de escalones se lee mejor que un degradado. Un cruce sin dato no es un cero: se queda sin color.

  **El hover va por delegación** — es lo único de los cuatro tipos de negocio que toca el JavaScript. Un heatmap de 365×7 son 2.555 celdas; un listener por celda cuesta 30 ms por frame, así que hay **un solo `pointermove`** en la rejilla que lee el `data-*` de la celda bajo el ratón. El resalte, en cambio, es `:hover` de CSS puro: una pseudoclase sobre miles de nodos es barata, miles de listeners no.

- **Guardia de peso del bundle en CI** (`npm run size`). «El JavaScript es poco» es una promesa de la documentación, y una promesa que nadie mide deja de ser verdad sin que nadie se entere.

#### El principio que ordena todo el módulo

**El color nunca es un valor: es un token.** Las series se pintan con `var(--kore-chart-1)`, así que **al cambiar de tema el gráfico se repinta solo, sin ejecutar una sola línea de JavaScript**. Verificado en Chrome, Firefox y WebKit.

Eso es lo contrario de lo que hace un motor de `<canvas>`, donde el color es un valor de JS y hay que volver a ejecutar código en cada cambio de tema — con las consecuencias que lleva años pagando el resto del ecosistema Laravel: colores que se intercambian solos entre gráficos, y gráficos que necesitan una recarga para verse bien en modo oscuro.

Y como el `<svg>` lo emite el servidor, **el morph de Livewire deja de ser una amenaza y pasa a ser el mecanismo de actualización**: cambias el dato en PHP y Livewire actualiza el atributo `d` del `<path>` **sin recrear el nodo**. Sin `wire:ignore`, sin `chart.update()`, sin una instancia de JavaScript que proteger.

#### Detalles que quizá quieras conocer

- **No existe un `type="mixed"`.** Un gráfico de barras con una línea encima son dos marcas, una detrás de otra. El orden en que las escribes es el orden en que se pintan.
- **Los ejes dicen números redondos.** El algoritmo de ticks es un puerto del de d3 (ISC, con atribución en [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md)) y da exactamente los mismos valores. El reparto ingenuo que usa el resto del ecosistema PHP produce ejes que dicen "1.224".
- **Un `null` no es un cero.** La línea se corta en el hueco, en vez de dibujar una caída al suelo que nunca ocurrió. Y la curva monótona reinicia sus tangentes ahí, para no inventarse una curva por encima del vacío.
- **Los datos van también en una tabla, escondida para el lector vidente.** Un `<svg>` es tan mudo para un lector de pantalla como un `<canvas>`; como los datos ya están en PHP, la tabla sale gratis. Nadie más en el ecosistema la sirve.
- **Sin leyenda ni tooltip, el gráfico no carga JavaScript.** Y el resize no lo necesita nunca: la geometría está en porcentajes, no en píxeles.
- **La paleta no se cicla.** La novena serie no existe: repetir el color de la primera es peor que no pintarla. Si lo intentas, salta una excepción.

### Fixed

- **El tooltip enseñaba los datos del render ANTERIOR después de un morph.** El morph de Livewire va en las dos direcciones y solo se atendía una: lo que escribe el cliente, el morph lo borra (y eso ya se reaplicaba); lo que escribe el servidor, Alpine no lo relee. El payload solo se leía en `init()`, y un morph **no reinicializa el `x-data`**. Así que cualquier `wire:model` que cambiara el dataset repintaba el `<path>` con los valores nuevos y dejaba el tooltip enseñando los viejos. Sin ningún error.

- **La tabla accesible arrastraba scroll horizontal a toda la página.** `sr-only` esconde la caja con `width: 1px`, pero sobre una `<table>` **ese ancho se ignora**: el algoritmo de layout de tablas lo acota por abajo al `min-content`. Medido en un móvil de 375 px: la tabla ocupaba **321 px de ancho**. El `clip-path` sí aplicaba —así que no se veía y nadie se enteró— pero la caja seguía ocupando. Ahora el `sr-only` va en un `<div>`, que sí obedece.

- **Las etiquetas del eje X se salían de la tarjeta.** Se anclaban por el borde la primera y la última, y eso funcionaba porque las únicas que se salían eran las de los extremos… en una escala de bandas. En una escala continua un tick puede caer en el **98,9 %** —ni centrado ni en el borde—, ahí ningún umbral salta, y media etiqueta se va fuera. Ahora el servidor emite **lo que mide** la etiqueta, en `ch` (no puede medir texto, pero sí contarlo), y el CSS la acota con un `clamp`: la centra sobre su tick si cabe y la apoya en el borde si no. Es exacto por construcción, no tiene umbrales que afinar, y de paso resuelve los dos casos que el anclaje trataba a mano.

- **Las etiquetas del eje X se pisaban unas a otras en un móvil.** Con categorías largas («Coste de ventas», «/api/pedidos») o un eje de tiempo denso, las etiquetas se solapaban. El `width` en `ch` sólo evitaba que se salieran de la tarjeta — el solape con la vecina es un choque distinto, y no lo tapaba nada. Ahora el servidor emite el **hueco** que cada etiqueta tiene hasta su vecina (lo sabe: conoce dónde cae cada tick), y el CSS la acota a lo menor de su ancho o ese hueco: una etiqueta larga se recorta con puntos suspensivos en vez de meterse encima de la de al lado. Se quitó de paso el adelgazado por *container-query*, que peleaba con esto y dejaba las etiquetas recortadísimas y con huecos.

- **El tirador del zoom era imposible de agarrar en un móvil con el zoom muy cerrado.** Al 5 % la ventana del contexto medía ~16 px, y con dos asas encima no había dónde pinchar. Ahora tiene un mínimo de 24 px — ancho de dedo, no de píxel.

- **Las barras de una cascada tenían las esquinas de abajo en pico.** Una barra normal se redondea sólo por la punta, pero una de cascada FLOTA —no crece desde el suelo—, así que se le redondean las cuatro esquinas por igual.

- **`max-labels` no funcionaba. Nunca.** Una prop con guion declarada en `@props` **no se puede leer con `$attributes->get()`**: Blade ya la ha extraído del bag. Así que `$attributes->get('max-labels')` devolvía `null` siempre, y el tope de etiquetas del eje X no se aplicaba jamás — salían todas. Salió a la luz al añadir `max-gap`, que fallaba igual.

- **Dos gráficos con stream en el mismo componente Livewire eran dos temporizadores.** Un refresco no actualiza un gráfico: actualiza el **componente entero**. Así que había el doble de round-trips contra el servidor, el dato avanzaba al doble de velocidad, y —lo peor— **poner el ratón sobre uno no paraba el otro**: el temporizador del de al lado seguía moviéndole los números bajo el cursor. Ahora conduce uno solo, y el ratón sobre cualquiera los para a todos.

- **La etiqueta de una fila no bajaba de los minutos.** En un gráfico en vivo muestreado cada dos segundos, los treinta puntos del mismo minuto se llamaban todos «21:35»: el tooltip dejaba de decir de cuál hablaba, y la tabla accesible tenía treinta filas con el mismo nombre.

- **Un color mal escrito dejaba la serie INVISIBLE, sin decir nada.** `color` se colaba tal cual al CSS, así que `color="chart-4"` —que es lo primero que uno prueba— acababa en `--kore-series: chart-4`, que no es un color válido: la serie no se pintaba. Medido en la demo, un gráfico de barras entero, invisible. Y lo mismo con cualquier errata (`destructiv`, `rojo`, `blue-500`).

  Ahora `chart-1`…`chart-8`, `seq-1`…`seq-7` y `ord-1`…`ord-7` son tokens válidos, un color CSS explícito (`#e11d48`, `oklch(…)`, `var(…)`) pasa, y **cualquier otra cosa lanza**. Un color que no existe no puede dejar el gráfico en blanco: tiene que decirlo.

- **En una barra apilada se redondeaba cada tramo, no la columna.** La pila se veía como una torre de piezas sueltas en vez de como una barra partida en tramos. Ahora sólo lleva el redondeo la **punta** — y la punta es el último tramo **con valor**, no el último que declaraste: si a un mes le falta la serie de arriba, la punta pasa a ser la de debajo. Un `0` no cuenta (dibuja un tramo de altura sub-píxel, y redondearlo dejaría cuadrado el que sí se ve), y una barra negativa la redondea abajo, que es hacia donde crece.

- **La canaleta del eje Y medía casi el doble de lo que ocupaban sus etiquetas**, y ese hueco vacío empujaba el gráfico entero a la derecha. En un móvil, 31 px de 277 — un 11 % del ancho, robado al dato. Eran dos errores que se multiplicaban:

  - **`ch` se resolvía con la fuente equivocada.** La canaleta heredaba los 16 px del contenedor (`1ch = 10,08 px`) mientras las etiquetas se pintan a 12 px (`1ch = 7,56 px`): un 33 % de más. Ahora fija su propia fuente, la misma con la que pinta.
  - **Se contaba cada carácter como un `ch`.** Medido a 12 px: una cifra mide 1,00 ch (exacto, por `tabular-nums`), pero el punto mide 0,47 y el espacio 0,45. «7.000 €» pedía 7,5 ch cuando ocupa 5,9. Ahora cada carácter pesa lo suyo. Ojo al `%`, que mide **1,47 ch** —más que una cifra—: contarlo como 1 dejaría la etiqueta fuera de la tarjeta.

  Medido: el área de trazado pasa de 193 a 221 px en un móvil, con 3 px de holgura y ninguna etiqueta fuera.

- **En un móvil se leía «EneFeb».** Las etiquetas del eje X se anclaban por su borde —la primera y la última— para no salirse de la caja. Pero **dónde hay que anclar depende del dato, no del orden**, y aplicarlo a ciegas rompe uno de los dos casos:

  - **Con barras**, el tick cae en el CENTRO de la banda y tiene sitio de sobra a los lados. Anclar la primera por su borde izquierdo la empujaba media anchura a la derecha, justo encima de la siguiente: «EneFeb».
  - **Sin barras** (una línea, un área), el primer punto cae en `x=0`, pegado al eje Y. Centrar la etiqueta ahí le mete media anchura debajo de la canaleta del eje Y, y el eje X parece correrse.

  Así que ahora lo decide el servidor, que es quien sabe dónde cae cada tick — y lo decide por la **posición**, no por el orden: con el adelgazado de etiquetas, el último tick pintado no siempre cae en el 100. Medido a 375 px en los dos casos: cero solapes, y las etiquetas de una escala de punto se apoyan exactamente en el borde del área.

- **El donut se salía de la tarjeta en pantallas estrechas.** El arco (16rem) y su leyenda no caben uno al lado del otro en 277 px. Por debajo de 26rem se apilan. Es una *container query*, no una *media query*: el gráfico responde al ancho de su contenedor, así que uno metido en un sidebar estrecho se apila también aunque la ventana sea de escritorio.

- **Once componentes escribían parte de su estado en el `x-data` de un ancestro, no en sí mismos.** Alpine no llama a los métodos de un componente con `this` = el objeto del componente: los llama con un Proxy que fusiona toda la pila de scopes, y su trampa `set` acaba así:

  ```js
  if (!target) target = objects[objects.length - 1];   // el x-data MÁS EXTERNO
  ```

  O sea: `this.foo = x` sobre una propiedad **no declarada** en la factoría no se guarda en el componente — se guarda en el `x-data` ancestro más externo de la página, que comparten todos sus hermanos. Las consecuencias eran silenciosas: cinco gráficos en una página enseñaban en el tooltip los datos del quinto; dos tablas compartían el `ResizeObserver`; y `_floatingCleanup` —que usan seis componentes distintos— se pisaba entre ellos, de modo que cerrar un dropdown ejecutaba la limpieza de un select. Sin un solo error en consola.

  Afecta a `chart`, `datatable`, `select`, `dropdown`, `tooltip`, `overlay`, `upload`, `datepicker`, `color-picker` y `time-picker`. Todas las propiedades están ahora declaradas, y `tests/js/alpine-scope.test.js` recorre el AST de cada componente para que no vuelva a colarse ninguna.

- **El tooltip del gráfico se anclaba al borde superior del área de dibujo**, así que salía siempre flotando por encima del gráfico, tapando el título. Ahora la X se engancha al dato —para que no tiemble mientras el ratón se mueve dentro de la misma banda— y la Y sigue al cursor.

- **Las series perdían sus decimales.** El eje deduce los suyos del paso entre ticks; una serie no tiene paso, así que se escribía con cero decimales: un sensor que marcaba 21,4 °C aparecía en el tooltip y en la tabla accesible como «21». Ahora cada serie deduce los decimales de sus propios valores, los mismos para toda la serie.

- **`theme.js` no avisaba al cambiar el tema del sistema operativo.** `setMode()` sí despachaba `theme-changed`, pero el listener de `matchMedia` no. Con `mode: 'system'` —que es el valor por defecto— cambiar el tema del SO repintaba el CSS sin avisar a nadie, así que cualquier consumidor que necesitara releer colores desde JavaScript se quedaba con el tema anterior. Y solo en la configuración por defecto, que es la peor forma de encontrar un bug.

### Changed

- **Todo se apaga con la misma prop: `:show`.** Los ejes y la rejilla salen por defecto (un gráfico sin ejes no se lee); el tooltip y la leyenda hay que pedirlos. Y en una serie, `:show="false"` **no es lo mismo que envolver la marca en un `@if`**: el color se asigna por orden de registro, así que quitar la marca del árbol de Blade hace que la siguiente herede su color y **todas las series de detrás se recoloquen** — el lector, que ya sabía que «Ingresos» era la naranja, se encuentra otra cosa naranja. Con `:show="false"` la marca se registra, **se queda con su color** y no se dibuja. Lo que sí desaparece es su trazo, su entrada en la leyenda, su copia en el payload, su columna en la tabla accesible y su aportación al dominio, **así que el eje se reajusta solo** — gratis, porque la geometría se calcula en el servidor.

  En el tooltip, `:show="false"` tampoco lo esconde con CSS: **no emite el payload**, que a 2.000 puntos pesa más que el propio trazo.

  El `hide` de los ejes desaparece en favor de `:show` (invertido y a contracorriente del resto). `1.6.0` no llegó a publicarse, así que no rompe a nadie.

- **Dos interruptores que no encendían nada.** `<x-kore::chart.tooltip :crosshair="false" />` registraba la prop y **no la leía nadie**: el crosshair se pintaba siempre. Y `ChartFrame::$grid` llevaba desde el principio en `true` sin que nada le escribiera jamás: la rejilla no se podía quitar. Ahora `crosshair` se honra y la rejilla se apaga con `<x-kore::chart :grid="false">`.

- **Un donut ya no descarta marcas en silencio: lanza.** Una línea, un eje o un tooltip dentro de un donut no se pintaban, no avisaban, y encima el gráfico montaba un componente de Alpine que no hacía nada. Escribías una marca y el gráfico decidía por su cuenta que no valía. Es la misma regla que al mezclar escalas: no se adivina. (Y el donut ya no monta Alpine: su única interacción es CSS puro.)

- **En el donut, al posarte sobre un arco se enciende su fila de la leyenda, y al revés.** Sin esa relación hay que emparejar el color a ojo entre el arco y la leyenda, que es lo que peor funciona —y peor todavía con daltonismo—. Está hecho con `:has()` en CSS puro: el arco y su fila comparten un `data-slice`, así que **no hace falta ni una línea de JavaScript** ni una segunda copia de los datos en el DOM. El donut sigue sin tooltip a propósito: su leyenda ya imprime etiqueta, valor y porcentaje de cada porción.

- **`floating.js` acepta una referencia virtual.** El tooltip de un gráfico no cuelga de un elemento: cuelga de un punto de datos. Y como un ratón moviéndose no dispara scroll, resize ni mutación, el `cleanup` que devuelve `startFloating()` expone ahora un `update()` para pedir el reposicionamiento a mano. Los cuatro componentes que ya lo usaban no cambian.

> **Limitaciones que conviene conocer.** El eje X es categórico: las fechas se pre-formatean en PHP y entran como categorías (una escala temporal de verdad es otro algoritmo entero). Los puntos de la línea son opt-in, porque son un `<div>` por dato. El techo práctico son unos 2.000 puntos por serie, y no lo pone el dibujo sino el peso del HTML. Ocultar una serie desde la leyenda no reescala los ejes. Y no hay zoom, ni pan, ni tipos exóticos.

---

## [1.5.0] — 2026-07-13

**App Shell.** El chasis de la aplicación —sidebar, barra superior y el layout que los coordina— pasa a ser parte de la librería. Con esto se monta un panel de administración completo sin escribir layout a mano.

### Added

- **`<x-kore::shell>`** — el layout. Los sidebars se le anuncian solos al renderizarse, así que reserva el espacio del contenido sin recibir props duplicadas ni inspeccionar el HTML.
- **`<x-kore::sidebar>`** — navegación lateral colapsable, con drawer automático en móvil, modo rail y soporte para dos sidebars a la vez (uno a cada lado).
- **`<x-kore::sidebar.item>`** — enlaces, desplegables a cualquier profundidad, badges y detección de ruta activa.
- **`<x-kore::sidebar.group>`** — secciones con título, opcionalmente plegables.
- **`<x-kore::sidebar.toggle>`** — el botón de menú. Habla con el store de Alpine, así que funciona desde cualquier punto de la página; en escritorio colapsa y en móvil abre el drawer.
- **`<x-kore::navbar>`** — barra superior con tres zonas y el botón de menú incorporado.

Documentación en [`docs/shell/`](docs/shell/getting-started.md).

#### El principio que ordena todo el módulo

**El estado lo resuelve el servidor y lo estampa en el HTML. Alpine solo cambia atributos.**

- **El sidebar recuerda si está colapsado en una cookie** (`kore_sidebar`), no en `localStorage`. La cookie viaja al servidor, así que Laravel emite el ancho correcto **en el primer paint**: cero parpadeo, cero JavaScript en el camino crítico, y el sidebar sale bien aunque el JS no cargue nunca. Con `localStorage` el estado solo se sabría tras arrancar Alpine, y el sidebar aparecería ancho y se encogería a la vista del usuario en cada carga.
- **La ruta activa y los sub-menús abiertos se calculan en PHP.** Si estás en una hoja del árbol, toda su rama sale ya desplegada en el HTML — a cualquier profundidad. Nada de menús que se abren de golpe cuando arranca el JavaScript.
- **El layout móvil es CSS puro.** Media queries, no `matchMedia`: correcto en el primer paint y en cada resize.

#### Detalles que quizá quieras conocer

- Con el sidebar en iconos, los sub-menús salen en **flyouts apilados**: un menú anidado se abre *al lado* de su padre, no en su lugar.
- Los **badges numéricos se mudan a la esquina del icono** al colapsar, con tope configurable (`badge_max`, por defecto `99` → `"99+"`). El valor acortado es solo el visual: un lector de pantalla sigue anunciando el número real.
- **Accesibilidad**: navegación completa por teclado (`↑↓`, `Home`/`End`, `←→` para los sub-menús), focus trap **solo** en el drawer móvil (en escritorio el sidebar es parte de la página, y atrapar el foco ahí sería un fallo, no una mejora), y los enlaces conservan su nombre cuando la etiqueta desaparece al colapsar.
- **`shell.badge_max`, `shell.sidebar.*` y `shell.navbar.*`** en `config/kore-ui.php`. Los anchos son longitudes CSS, no clases de Tailwind: alimentan las custom properties que gobiernan el layout.

> **Una limitación de CSS, no de KoreUi:** el sidebar es `position: fixed` y deja de anclarse a la ventana si un **ancestro** del shell tiene `transform`, `filter` o `contain`. Si aparece en un sitio raro, busca eso.

### Fixed

- **El selector de tema estaba muerto en las variantes `toggle` y `segmented`.** No declaraban `x-data`, y Alpine solo evalúa directivas dentro de un árbol que lo tenga: sus `x-on:click` nunca se registraban y pulsarlos no hacía nada, **sin ningún error**. Funcionaba por accidente en las apps cuyo layout tuviera un `x-data` colgado del `<html>`, y estaba roto en las demás.
- **MCP** — los tokens del shell (`--kore-sidebar-width: 16rem`) se publicaban como si fueran **colores**, con una clase `bg-kore-sidebar-width` inexistente. Ahora tienen su propia categoría `layout`. Además, el filtro por categoría solo conocía 4 de las 9 que existen.

### Changed

- **`<x-kore::toolbar>` acepta `:role="false"`** para no emitir `role="toolbar"`. Ese rol le promete a un lector de pantalla un widget que se recorre con las flechas; una cabecera de página no lo es. Retrocompatible: por defecto se sigue emitiendo.
- **El bloqueo de scroll sale de `overlay.js`** a `utils/scroll-lock.js`, con conteo de dueños. Es un recurso global: sin ese conteo, cerrar un modal devolvería el scroll a la página con el drawer del sidebar todavía abierto.

---

## [1.4.1] — 2026-07-13

### Fixed

- **DataTable — el overlay de carga se quedaba pegado sobre la tabla.** Regresión introducida en 1.4.0: los datos se veían, pero el spinner no desaparecía nunca.

  La causa está en cómo Livewire oculta estos overlays en el primer render. No usa un selector genérico, sino un `<style>` que enumera los selectores de atributo **uno a uno**:

  ```css
  [wire\:loading], [wire\:loading\.delay], [wire\:loading\.flex], [wire\:loading\.block], … { display: none }
  [wire\:loading\.delay\.short], [wire\:loading\.delay\.long], … { display: none }
  ```

  Existe `[wire:loading.delay]` y existe `[wire:loading.flex]`, pero **no existe ningún selector para la combinación *delay + display***. Como el selector es el nombre literal del atributo, un `wire:loading.delay.flex` no encaja con ninguno y nunca recibe su `display:none` inicial: nace visible. Y el JavaScript solo lo apaga al **terminar** una petición, así que si en la primera carga no hay ninguna —o dura menos que el delay— no queda nadie que lo apague.

  El arreglo es declarar el estado inicial a mano, con `style="display: none"` en el propio overlay. El modificador `.flex` sigue siendo necesario para que el spinner quede centrado (sin él Livewire escribe `display:inline-block` en el style inline y pisa la clase `flex`). **Las dos piezas van juntas o no van.**

  Si tienes un template del DataTable publicado, aplícale el mismo cambio:

  ```diff
  - <div wire:loading.delay.flex class="absolute inset-0 …">
  + <div wire:loading.delay.flex style="display: none" class="absolute inset-0 …">
  ```

---

## [1.4.0] — 2026-07-13

Release de correcciones. Dos bugs visibles y una pieza de configuración que llevaba tiempo mintiendo.

### Fixed

- **DataTable — overlay de carga desalineado.** El overlay usaba `wire:loading.delay` sin modificador de display, así que Livewire le aplicaba `display:inline-block` en línea, pisando la clase `flex` y dejando el spinner arriba a la izquierda en vez de centrado. Ahora usa `wire:loading.delay.flex`.

  > ⚠️ **Este cambio salió incompleto y provocó una regresión: el overlay se quedaba pegado sobre la tabla. Corregido en 1.4.1.** Al restaurar el modificador `.flex` se perdió el `display:none` inicial, porque Livewire no tiene selector CSS para la combinación *delay + display*. Ver la entrada de 1.4.1 para la explicación completa.
- **DataTable — overlay que no tapaba la tabla al hacer scroll.** El overlay vivía dentro del contenedor con `overflow-x-auto`, donde un `absolute inset-0` se desplaza junto al contenido: en cuanto había scroll horizontal, las columnas de la derecha quedaban al descubierto durante la carga. Ahora se ancla a un padre que no hace scroll.
- **Toast — el hover borraba la descripción.** `@mouseleave` colapsaba el toast siempre, ignorando `autoExpand`. Bastaba con rozar un toast con el cursor para que perdiera su descripción de forma permanente. El hover ahora solo puede **añadir**: nunca colapsa por debajo del estado de reposo.
- **Toast — un `loading` resuelto no se volvía a expandir.** `resolve()` activa `autoExpand` al llegar una descripción, pero el estado se copiaba una sola vez al montar el componente Alpine, así que el cambio nunca llegaba a la vista.

### Added

- **`feedback.toast.expand_delay` (150 ms) y `collapse_delay` (300 ms)** — estaban en la config y se pasaban al front, pero no los consumía nadie. Ahora se aplican de verdad, con cancelación del temporizador pendiente: pasar el cursor de largo sobre un toast ya no lo abre y lo cierra de golpe.
- **Tests JS del sistema de feedback** — `tests/js/feedback.test.js` cubre `isExpanded()` y `setHovered()` con temporizadores simulados.

### Deprecated

- **`expandable` en el payload del toast.** Duplica lo que ya dicen `description`, `actions` y `options`, y la librería no la lee: el front deriva el estado con `isExpanded()`. Sigue publicándose para no romper templates ya publicados que la consulten, y **se elimina en 2.0**. Si tienes un template de toast publicado, deja de depender de ella.

### Chore

- **Grafo de conocimiento (graphify)** — se versiona `graphify-out/` (informe y grafo) y se añade `.graphifyignore` para que `vendor/`, `node_modules/` y `dist/` no inflen el corpus.

---

## [1.3.0] — 2026-06-04

### Security

Endurecimiento de los 6 hallazgos P0 de la auditoría exhaustiva (cliente→servidor). Cambios compatibles: el uso normal de la API no se ve afectado.

- **Confirm callbacks** — `kore:confirm-callback` solo ejecuta métodos previamente autorizados server-side por `confirm()->onConfirm()/onCancel()->send()` (lista `#[Locked]` consumida en un solo uso). Bloquea eventos forjados desde el navegador que invocaban métodos arbitrarios o `protected`/`private` (p.ej. `runBulkAction`) saltándose el diálogo de confirmación.
- **DataTable export** — `exportAs()` valida `isExportEnabled()` (403) y el formato contra `getExportFormats()` (404); ocultar el botón en la UI ya no era una autorización.
- **Spotlight providers** — `$providers` es `#[Locked]` y solo se instancian clases que extienden `SpotlightProvider`: cierra la instanciación arbitraria de clases vía `app($class)`.
- **Spotlight SSRF** — `searchDependency()` valida el esquema (http/https) y bloquea hosts privados/loopback/link-local (169.254.x, RFC1918) antes de cualquier petición HTTP.
- **Spotlight navegación** — las URLs de acción `url` (de items y de resultados remotos) se sanean server-side; se neutralizan `javascript:`/`data:`/`//host`.
- **ColorColumn** — el valor de celda se escapa con `@js()` en el handler de copiado (XSS almacenado) y se valida como color CSS antes de inyectarlo en `style` (inyección CSS).
- **UrlSanitizer** — rechaza URLs scheme-relative (`//host`, open-redirect) y elimina caracteres de control que disfrazan esquemas peligrosos (`java\tscript:`).
- **DataTable** — `tableName` ahora es `#[Locked]`.

### Added

- **Accesibilidad de formularios** — todos los controles (`input`, `textarea`, `password`, `number`, `maskable`, `checkbox`, `radio`, `toggle`, `radio-group`) exponen `aria-invalid` y asocian su error/hint/descripción al control vía `aria-describedby` con ids deterministas (WCAG 3.3.1 / 4.1.2).
- **Token `--kore-warning-text`** — tono accesible (AA) para `warning` usado como texto en variantes soft/outline de `badge`, `chip` y `alert`.
- **Red de tests JS (Vitest)** — `tests/js/` con scripts `test:js` / `test:js:watch` y primer test de la lógica de `number.js`.
- **CI (GitHub Actions)** — Pest en matriz PHP 8.2–8.4, Vitest y build de assets en cada push/PR.

### Fixed

- **Dark mode** — `ring-offset-kore-bg` en los componentes con foco visible (elimina el halo blanco del anillo de foco en modo oscuro).
- **Rendimiento** — `loading="lazy"` + `decoding="async"` en `ImageColumn` y avatares.
- **i18n** — registrada la clave `datatable.translations.edit_unauthorized` (estaba leída pero ausente del config).
- **Docs** — prefijo de componente corregido a `x-kore::` (dos puntos) en documentación y CHANGELOG.

---

## [1.2.0] — 2026-06-03

Auditoría completa del DataTable en tres sprints (seguridad, escalabilidad y accesibilidad), más selección persistente entre páginas. Es el release que convierte el DataTable en un componente apto para producción con datasets grandes.

### Security

- **Ordenación arbitraria** — `sorts` se valida contra la whitelist de columnas ordenables y la dirección se normaliza en `applySorts()`. Antes se podía ordenar por columnas no expuestas, y una dirección inválida provocaba un 500.
- **IDOR en edición inline** — `updateCell` resuelve el registro vía `query()->where()` en lugar del modelo estático, respetando los scopes del componente. Si el registro no cae dentro del scope, se emite `edit-error` en vez de editarlo.
- **CSV injection** — `CsvExporter` neutraliza las celdas que empiezan por `=`, `+`, `-`, `@`, tab o retorno de carro.
- **Comodines LIKE** — la búsqueda escapa `%` y `_` con cláusula `ESCAPE` explícita (compatible entre motores).

### Added

- **Selección server-side persistente entre páginas.** La selección pasa a ser estado del componente (`$selected`, `$selectAllMatching`) en vez de vivir solo en Alpine, donde se reiniciaba al paginar o al hacer morph. Incluye banner con el total ("N seleccionados, incl. otras páginas"), botón *seleccionar todo lo que coincide* y `executeBulkActionMatching()` / `getAllMatchingIds()`, que operan sobre la query filtrada en el backend.
- **Selección por rango con shift-click.**
- **Header sticky vía `maxHeight()`.** El `sticky` del `thead` no ancla dentro de un contenedor con `overflow-x`, porque scrollea con la página. `maxHeight` convierte el wrapper en una región de scroll interno (`overflow-auto` + `max-height`), que es donde el sticky sí funciona.
- **`Column::maxWidth()`** — truncado con `title` en `th` y `td`.
- **Persistencia de `perPage` en la URL**, validada contra las opciones permitidas y respetada en `mount()`.
- **`docs/getting-started.md`** — guía de instalación que refleja el enfoque de `@koreScripts` (bundle precompilado, sin imports npm).
- **`docs/data/hardening.md`** — documenta las garantías de seguridad y la API nueva de la auditoría.

### Changed

- **Cap real de filas en export.** El `Exporter` recibe `maxRows` y `CsvExporter` lo respeta con un contador: `chunk()` ignora `->limit()`, así que hasta ahora se exportaba el dataset entero pese al límite.
- **`presetCounts` cacheado** (`#[Locked]`, invalidado tras bulk o edición inline). Antes se lanzaba un `COUNT` por preset **en cada render**.
- **Offsets de columnas fijadas** recalculados en cliente midiendo anchos reales, con un único hook `morphed` global y `resize` con throttle.
- **Responsividad por contenedor** — `ResizeObserver` sobre el root en lugar del viewport.
- **Debounce de 500 ms** en los filtros `number` y `number-range`.
- **Dropdown de export teleportado a `body`** (z-50), con la escala de z-index documentada.

### Fixed

- **Hook `morphed` de pinning** — envuelto en `try/catch` con validación de `el`. Una excepción ahí rompía el ciclo de morph de Livewire y dejaba `wire:loading` congelado, con el overlay de carga visible para siempre.
- **Botón anidado en columnas con interacción propia** — `ColorColumn->copyable()` envolvía la celda en el botón genérico de copiar mientras `color.blade.php` renderiza su propio `<button>`, produciendo un `<button>` dentro de otro. HTML inválido que rompía el DOM y el scope de Alpine. Las columnas `color`, `component` y `action` quedan excluidas del wrapper genérico.
- **Overlay de carga descentrado** — se sustituye el modificador `.flex` por la clase `flex` fija. *(Nota: este diagnóstico resultó ser incorrecto y se revirtió en 1.4.0; ver esa entrada.)*
- **Agregaciones** — el campo se valida con regex y se envuelve con `wrap()` en el `selectRaw`; `AVG` sobre un dataset vacío preserva `null` en vez de devolver `0`.
- **Ordenación con dot-notation** — se omiten esas columnas en lugar de generar SQL inválido.
- **`Ctrl+A` secuestrado** — el listener de teclado se acota al hover/foco del datatable, en vez de capturarlo en toda la página.
- **Guard de `per_page_options` vacío** en `updatedPerPage`.
- **Orden determinista en export** — `orderBy(pk)` como tiebreaker antes de `chunk()`.

### Accessibility

- `scope="col"`, `aria-sort` dinámico y `aria-label` en los botones de orden (tabla, modo collapse y componente genérico).
- `aria-selected` en las filas seleccionadas y `aria-label` en checkboxes (tabla, card y collapse) y en los botones de cierre de las pills.
- `aria-label` en las flechas de paginación deshabilitadas.

---

## [1.1.0] — 2026-04-04

### Added

- **Pipeline de build y directiva `@koreScripts`.** Los plugins de Alpine se compilan en un bundle IIFE (`dist/kore-ui.js`) que se sirve desde una ruta de Laravel. `@koreScripts` inyecta el `<script>` que apunta a ella, así que instalar la librería ya no obliga a tocar la configuración de npm de la aplicación ni a importar desde `vendor/`.

---

## [1.0.0] — 2026-04-03

Primera versión estable. El salto desde `0.2.5` marca la estabilización de la API pública: a partir de aquí el versionado sigue SemVer con garantías de compatibilidad. El único cambio de código respecto a `0.2.5` es el fix de abajo.

### Fixed

- **`dropdown.item` sin compilar en `ActionColumn`.** Blade no compila componentes `<x-kore::*>` que llevan directivas `@if` dentro de su etiqueta de apertura. Se sustituyen por expresiones ternarias (`:style`, `:target`, `:rel`) para que las acciones por fila se rendericen.

---

## [0.1.0] — 2026-03-18

Primera versión pre-release de kore-ui. Incluye el sistema base completo con overlay, feedback, theming, 18 componentes de formulario y 20+ componentes UI.

### Added

#### Sistema Overlay
- `OverlayManager` — Componente Livewire que gestiona el stack de overlays
- Tipos soportados: modal, drawer (izquierda/derecha), bottom-sheet, fullscreen, confirm
- Stacking con transiciones, close on escape/click-away, scroll locking
- Swipe-to-close en bottom-sheet
- Backdrop blur configurable
- Trait `HasOverlayBehavior` para integrar en cualquier componente Livewire
- Plugin Alpine `KoreOverlay` para gestión de estado frontend

#### Sistema Feedback
- `FeedbackManager` — Componente Livewire para toasts
- `ConfirmDialog` — Componente Livewire para diálogos de confirmación
- API fluida: `kore_toast()->success('Guardado')->send()`
- Posiciones configurables, timeout, swipe-to-dismiss
- Agrupación de toasts idénticos con contador
- Sole mode para limpiar toasts previos
- Plugin Alpine `KoreFeedback`

#### Sistema de Theming
- `kore-theme.css` con tokens semánticos en OKLCH (`--kore-primary`, `--kore-bg`, etc.)
- Dark mode automático vía `.dark` / `[data-theme="dark"]`
- Tokens de radio (`--kore-radius-sm/md/lg/xl`)
- `<x-kore::theme-switch>` — Selector light/dark/system
- Alpine store `koreTheme` con detección de preferencia del sistema
- Anti-FOUC integrado

#### Componentes de Formulario (18)
- `<x-kore::input>` — Input con íconos, prefix/suffix, clearable, tamaños
- `<x-kore::textarea>` — Multi-línea con rows configurables
- `<x-kore::select>` — Native o custom con búsqueda, async, creatable, multi-select
- `<x-kore::checkbox>` — Con manejo de errores
- `<x-kore::radio>` — Con radio groups
- `<x-kore::toggle>` — Switch component
- `<x-kore::password>` — Con toggle de visibilidad e indicador de fortaleza
- `<x-kore::number>` — Input numérico con soporte de moneda (USD configurable)
- `<x-kore::datepicker>` — Calendario completo con rangos, time, presets, formato/locale
- `<x-kore::time-picker>` — Selector de hora
- `<x-kore::upload>` — Drag & drop, progreso, validación, retry, auto-upload
- `<x-kore::range>` — Slider con track/thumb sizing
- `<x-kore::rating>` — Estrellas/íconos configurables, clearable
- `<x-kore::tag-input>` — Entrada multi-valor con tags
- `<x-kore::color-picker>` — Grid de colores personalizable
- `<x-kore::maskable>` — Input con máscara
- `<x-kore::input-otp>` — Input de código OTP
- `<x-kore::field>` — Wrapper para label, hint y mensajes de error
- `<x-kore::float-label>` — Label animado en focus

#### Componentes UI (20+)
- `<x-kore::button>` — Tamaños (sm/md/lg), variantes (solid/outline/ghost/soft/link), colores, íconos
- `<x-kore::button-group>` — Agrupación de botones
- `<x-kore::alert>` — Notificaciones con ícono y título
- `<x-kore::badge>` — Indicadores de estado con variantes
- `<x-kore::card>` — Con imagen, colapsable, bordered/shadowed
- `<x-kore::dropdown>` — Menú popup con posicionamiento y modo persistente
- `<x-kore::tooltip>` — Tooltips con posicionamiento
- `<x-kore::avatar>` — Con fallback
- `<x-kore::avatar-group>` — Múltiples avatars agrupados
- `<x-kore::loading>` — Spinner de carga
- `<x-kore::page-loading>` — Overlay de carga full-page con blur
- `<x-kore::accordion>` — Secciones expandibles con variantes
- `<x-kore::divider>` — Separador visual
- `<x-kore::progress>` — Barra de progreso lineal
- `<x-kore::progress-circle>` — Progreso circular
- `<x-kore::stepper>` — Indicador de pasos (horizontal/vertical)
- `<x-kore::tab>` — Interfaz de pestañas (line/block)
- `<x-kore::toolbar>` — Contenedor de herramientas
- `<x-kore::skeleton>` — Placeholder con shimmer/pulse
- `<x-kore::empty-state>` — Estado vacío
- `<x-kore::chip>` — Componente compacto
- `<x-kore::timeline>` — Línea temporal con variantes
- `<x-kore::stats>` — Estadísticas con animación
- `<x-kore::clipboard>` — Copy-to-clipboard
- `<x-kore::kbd>` — Tecla de teclado
- `<x-kore::boolean>` — Indicador true/false con íconos configurables
- `<x-kore::speed-dial>` — FAB menu flotante
- `<x-kore::splitter>` — Layout redimensionable (horizontal/vertical)
- `<x-kore::carousel>` — Carrusel con autoplay, navegación, indicadores
- `<x-kore::tree>` — Árbol jerárquico con selección y filtrado
- `<x-kore::breadcrumbs>` — Migas de pan con JSON-LD, separadores configurables

#### Configuración
- `config/kore-ui.php` publicable con secciones: overlay, feedback, form, ui, breadcrumbs, theme
- Defaults sensatos para todos los módulos
- Helpers globales: `kore_toast()`, `kore_confirm()`, `kore_breadcrumbs()`

#### Infraestructura
- Service Provider con auto-discovery de Laravel
- Componentes anónimos con namespace `kore` (`<x-kore::*>`)
- Plugins Alpine registrados automáticamente via `alpine:init`
- Tests con Pest 3 + Orchestra Testbench 10

### Fixed
- Centrado vertical de modales usando `min-h-dvh`
- Scroll y layout de modales largos, drawers y bottom-sheets

---

[1.5.0]: https://github.com/koreui/kore-ui/releases/tag/v1.5.0
[1.4.1]: https://github.com/koreui/kore-ui/releases/tag/v1.4.1
[1.4.0]: https://github.com/koreui/kore-ui/releases/tag/v1.4.0
[1.3.0]: https://github.com/koreui/kore-ui/releases/tag/v1.3.0
[1.2.0]: https://github.com/koreui/kore-ui/releases/tag/v1.2.0
[1.1.0]: https://github.com/koreui/kore-ui/releases/tag/v1.1.0
[1.0.0]: https://github.com/koreui/kore-ui/releases/tag/v1.0.0
[0.1.0]: https://github.com/koreui/kore-ui/releases/tag/v0.1.0
