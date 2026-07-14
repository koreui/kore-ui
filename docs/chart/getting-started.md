# Gráficos

Gráficos de barras, líneas, áreas y donut. **Sin ninguna librería de JavaScript**: la geometría se calcula en PHP y el servidor te devuelve el `<svg>` ya dibujado.

## Los componentes

| Componente | Para qué |
|---|---|
| `<x-kore::chart>` | El gráfico. Le das los datos y dentro pones las capas |
| `<x-kore::chart.line>` | Una línea |
| `<x-kore::chart.area>` | Un área bajo la línea |
| `<x-kore::chart.bar>` | Barras. Con `stack` se apilan; sin él, se agrupan |
| `<x-kore::chart.donut>` | Un donut (o una tarta, con `inner="0"`) |
| `<x-kore::chart.waterfall>` | Una cascada: el puente entre un valor inicial y uno final. Ver [waterfall.md](waterfall.md) |
| `<x-kore::chart.axis-y>` | El eje Y: cuántos ticks y cómo se formatean |
| `<x-kore::chart.axis-x>` | El eje X: categorías, **fechas** o números. Ver [time-axis.md](time-axis.md) |
| `<x-kore::chart.legend>` | La leyenda. Al pulsarla, oculta la serie |
| `<x-kore::chart.tooltip>` | El tooltip y el crosshair |
| `<x-kore::chart.zoom>` | Zoom, pan y el mini-gráfico de contexto. Ver [zoom.md](zoom.md) |
| `<x-kore::chart.stream>` | Datos en vivo. Ver [streaming.md](streaming.md) |

## Ejemplo completo

```blade
<x-kore::chart :data="$ventas" x="mes" title="Ingresos y gastos">
    <x-kore::chart.bar  y="gastos"   label="Gastos" />
    <x-kore::chart.line y="ingresos" label="Ingresos" curve="monotone" />

    <x-kore::chart.axis-y :ticks="5" format="currency" />
    <x-kore::chart.axis-x />
    <x-kore::chart.legend />
    <x-kore::chart.tooltip />
</x-kore::chart>
```

```php
$ventas = [
    ['mes' => 'Ene', 'ingresos' => 1240, 'gastos' => 800],
    ['mes' => 'Feb', 'ingresos' => 3180, 'gastos' => 1500],
    // ...
];
```

**No hay tipos de gráfico.** No existe un `type="mixed"`: un gráfico de barras con una línea encima son dos marcas, una detrás de otra. El orden en que las escribes es el orden en que se pintan.

## Si el eje X son fechas, pásale fechas

```blade
<x-kore::chart :data="$lecturas" x="medido_en">
    <x-kore::chart.line y="temperatura" />
</x-kore::chart>
```

Objetos `DateTime` o `Carbon`, **no cadenas ya formateadas**. La escala se detecta sola, y con ella cada punto cae donde le toca en el calendario en vez de donde le toca en el array.

No es una mejora estética. Con las fechas como texto, un sensor que estuvo tres días caído se dibuja con sus lecturas pegadas una a otra: la caída **desaparece**, y el gráfico se ve perfectamente bien mientras miente. Ver [time-axis.md](time-axis.md).

## Cómo funciona (y por qué importa)

**El gráfico lo dibuja el servidor.** El `<svg>` llega ya hecho en el HTML de la respuesta, con el atributo `d` calculado en PHP. Consecuencias que se notan:

- **El primer paint es el bueno.** No hay un hueco que se rellena cuando arranca el JavaScript.
- **Con Livewire, el morph *es* la actualización.** Cambias el dato en PHP, Livewire morphea el `<path>` y ya está. Sin `wire:ignore`, sin `chart.update()`, sin una instancia de JavaScript que proteger. Está verificado: el morph actualiza el `d` **sin recrear el nodo**.
- **El gráfico funciona sin JavaScript.** Sin leyenda ni tooltip, no se carga ni un byte de JS.

**El color nunca es un valor, es un token.** Las series se pintan con `var(--kore-chart-1)`, `var(--kore-chart-2)`… así que **al cambiar de tema el gráfico se repinta solo, sin ejecutar nada**. Verificado en Chrome, Firefox y Safari.

Esto es lo contrario de lo que hace un motor de `<canvas>`: ahí el color es un valor de JavaScript, y hay que volver a ejecutar código y repintar en cada cambio de tema.

**El resize tampoco necesita JavaScript.** Toda la geometría está en porcentajes del área de trazado, no en píxeles. El navegador escala.

**Los datos van también en una tabla.** Un `<svg>` es tan mudo para un lector de pantalla como un `<canvas>`. Como los datos ya están en PHP, el componente emite además un `<table class="sr-only">` con todos los valores. No es opcional y no cuesta un byte de JavaScript.

## Apagar cosas: `:show`, nunca un `@if`

Todo se apaga con la misma prop. Los ejes y la rejilla salen por defecto (un gráfico sin ejes no se lee); el tooltip y la leyenda hay que pedirlos.

```blade
<x-kore::chart :data="$ventas" x="mes" :grid="false">
    <x-kore::chart.bar  y="gastos" />
    <x-kore::chart.line y="ingresos" :show="$verIngresos" />

    <x-kore::chart.axis-y :show="false" />
    <x-kore::chart.tooltip :show="$conTooltip" :crosshair="false" />
    <x-kore::chart.legend :show="$conLeyenda" />
</x-kore::chart>
```

**Y ahora la parte que importa: en una serie, `:show="false"` NO es lo mismo que quitar la marca con un `@if`.**

El color de una serie se asigna por **orden de registro**. Si envuelves una marca en un `@if` y la condición falla, la marca desaparece del árbol de Blade, **la siguiente hereda su color y todas las series de detrás se recolocan**. El lector, que ya sabía que «Ingresos» era la naranja, se encuentra otra cosa naranja. El gráfico se ve perfectamente bien y miente.

Con `:show="false"` la marca **se registra, se queda con su color y no se dibuja**. Las de detrás no se enteran.

Lo que sí desaparece: el trazo, su entrada en la leyenda, su copia en el payload del tooltip, su columna en la tabla accesible — y su aportación al dominio del eje, **así que el eje se reajusta solo**. Eso sale gratis porque la geometría se calcula en el servidor: es lo que un motor de JavaScript te cobraría con un `chart.update()`.

Y en el tooltip, `:show="false"` no lo esconde con CSS: **no emite el payload**. El payload es una segunda copia del dato en el DOM, y a 2.000 puntos pesa más que el propio trazo. El gráfico adelgaza de verdad.

Si ocultas todas las series, sale el estado vacío.

## En móvil

El gráfico responde al ancho de **su contenedor**, no al de la ventana: son *container queries*. Uno metido en un sidebar estrecho se adapta igual aunque la pantalla sea de escritorio.

Lo que cambia solo, sin que tengas que hacer nada:

- **El donut se apila.** El arco y su leyenda no caben uno al lado del otro por debajo de 26rem, así que la leyenda se va abajo y el arco se encoge conservando su proporción.
- **Las etiquetas del eje X se colocan según dónde caiga su tick.** Con barras el tick es el centro de una banda, así que la etiqueta va centrada y le sobra sitio. Sin barras, el primer punto cae en el borde mismo del área, pegado al eje Y, y ahí la etiqueta se apoya en el borde en vez de centrarse. Lo decide el servidor, que es quien lo sabe. (Anclar siempre la primera la empujaría media anchura a la derecha, encima de la siguiente: se leería «EneFeb». Centrarla siempre la metería debajo del eje Y.)
- **El tooltip nunca se sale de la ventana.** Lo mantiene dentro `shift()` de Floating UI.

Si aun así te caben mal las etiquetas, baja `max_x_labels` o pre-formatea las categorías más cortas en PHP. **Rotarlas no es una opción**: `transform: rotate()` no ocupa layout, la fila del grid no crecería y se saldrían de la caja.

## Los colores

Hay **ocho colores de datos** (`--kore-chart-1` … `--kore-chart-8`) y se asignan **por orden de escritura de las marcas**. La primera marca es la 1, la segunda la 2, y así.

Son una escala aparte de los tokens semánticos, y a propósito: `--kore-success` significa *"esto va bien"*. Si lo usas para la serie 2, le estás diciendo al lector que la serie 2 va bien.

> **La novena serie no existe.** La paleta no se cicla: repetir el color de la serie 1 en la novena es peor que no pintarla, porque el lector deja de poder distinguirlas. Con más de ocho series, agrupa el resto en "Otros". Si lo intentas, el componente lanza una excepción.

Puedes forzar un color concreto cuando la serie *significa* algo:

```blade
<x-kore::chart.line y="errores" color="destructive" />
```

## Instalación

Los componentes vienen con la librería. Solo dos cosas, como el resto de KoreUi:

**1. `@koreScripts`** en el layout — y solo hace falta si usas leyenda o tooltip:

```blade
<body>
    {{-- tu contenido --}}

    @livewireScripts
    @koreScripts
</body>
```

**2. Que Tailwind vea las vistas del paquete**, en tu CSS:

```css
@import 'tailwindcss';
@import '../../vendor/koreui/kore-ui/resources/css/kore-theme.css';

@source '../../vendor/koreui/kore-ui/resources/**/*.blade.php';
```

## Configuración

En `config/kore-ui.php`, sección `chart`:

```php
'chart' => [
    'height' => '16rem',        // longitud CSS. Con la prop `aspect`, se ignora
    'ticks' => 5,               // una PISTA, no un contrato (ver abajo)
    'bar_padding' => 0.2,       // hueco entre barras, como proporción de la banda
    'max_x_labels' => 12,       // TOPE de etiquetas en un eje de CATEGORÍAS
    'x_ticks' => 6,             // OBJETIVO de ticks en un eje CONTINUO (fechas o números)
    'table_max_rows' => 500,    // tope de la tabla accesible
    'donut_highlight' => true,  // al posarte en un arco, se enciende su fila de la leyenda

    'empty_text' => 'No hay datos que mostrar',
    'empty_icon' => 'chart-line',

    'format' => [
        'decimal_separator' => ',',
        'thousands_separator' => '.',
        'currency' => '€',
        'currency_after' => true,   // "12 €" (es) vs "$12" (en)
    ],
],
```

## Limitaciones que conviene conocer

**`ticks` es una pista, no una promesa.** Si pides 5, te pueden salir 7. El algoritmo (el mismo que usa d3) prioriza que los valores sean redondos —1.000, 2.000, 3.000— sobre que sean exactamente los que pediste. Un eje que dice "1.224" es un eje roto, y prometer un número exacto de ticks obliga a eso.

**Los puntos de la línea no se pintan por defecto.** Con `dots` los activas, pero son un `<div>` por punto: con 10.000 puntos el HTML pesa 1,4 MB y mover el crosshair cuesta medio frame. Sin ellos, el trazo es **un solo nodo** y da igual cuántos datos haya.

**El techo son unos 2.000 puntos por serie.** No lo pone el rendimiento del dibujo, lo pone el peso del HTML. Por encima, agrega o decima los datos en PHP.

**Ocultar una serie desde la leyenda no reescala los ejes.** La serie desaparece, pero el eje se queda como estaba. (Con `:show="false"` en el servidor **sí** se reescala: la serie sale del dominio.)

**No hay zoom continuo con rueda ni pinch.** Sí hay zoom por arrastre, pan y un slider de contexto — todo resuelto en el servidor. Ver [zoom.md](zoom.md); ahí está escrito por qué la rueda no cabe.

**Hay datos en vivo, pero con techo.** Un refresco es un round-trip completo de Livewire: el techo honesto es **1 Hz con ≤ 200 puntos**, y a 10 Hz no aguanta ninguna arquitectura que dibuje en el servidor. Está medido y explicado en [streaming.md](streaming.md).

**No hay tipos exóticos** (candlestick, treemap, heatmap, mapas).

## Una regla de diseño que el componente te va a imponer

**Nunca hay dos ejes Y.** Si quieres comparar dos medidas de escalas distintas, son dos gráficos, no uno con dos escalas: un doble eje Y permite dibujar *cualquier* correlación con solo elegir bien las escalas, y por eso es el error más frecuente y más grave de la visualización de datos.
