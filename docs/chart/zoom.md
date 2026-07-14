# Zoom y pan

**El cliente manda dos números. El servidor hace el resto.**

## Uso

El zoom guarda su estado en el componente Livewire, así que necesita uno.

```php
use Livewire\Attributes\Url;

class Trafico extends Component
{
    /** El tramo visible, en % del dominio COMPLETO. `null` es todo. */
    #[Url(as: 'v', except: null)]
    public ?array $ventana = null;
}
```

```blade
<x-kore::chart :data="$this->trafico" x="dia" :window="$ventana">
    <x-kore::chart.area y="visitas" curve="monotone" />
    <x-kore::chart.axis-x />
    <x-kore::chart.tooltip />

    <x-kore::chart.zoom wire:model.live="ventana" />
</x-kore::chart>
```

Y ya está. Arrastra sobre el gráfico para ampliar un tramo, arrastra la ventana del contexto para desplazarte, doble clic para volver.

## Cómo funciona (y por qué no hay ni una escala en JavaScript)

**La ventana son dos porcentajes del dominio completo.** No dos fechas, no dos números del dominio: dos porcentajes. Eso tiene tres consecuencias, y las tres son la razón de que esto no cueste ni una línea de matemática de escalas en el cliente:

1. **Componer un zoom sobre otro es una regla de tres.** Arrastras del 20 % al 60 % de una vista que ya enseña `[40, 80]`, y la ventana nueva es `[48, 64]`. El cliente no necesita saber qué es una fecha, ni un locale, ni un formato.

2. **Es la misma operación para las tres escalas.** Recortar el espacio 0–100 a un tramo es un **remapeo afín**, y da igual que debajo haya categorías, fechas o números.

3. **El servidor devuelve el gráfico entero, ya resuelto.** Invierte el dominio (con `LinearScale::invert()`, que llevaba escrita desde el primer día sin que la usara nadie), elige los ticks nuevos, reescala el eje Y y calcula el `<path>`. Livewire lo morphea.

> **Lo más visible de todo:** al ampliar, **el eje temporal cambia de unidad solo**. Un año dice trimestres; ampliada una semana, el mismo eje dice días — con sus fronteras de calendario, en el idioma de la aplicación y con su línea de contexto. Un zoom en el cliente tendría que recalcular esos ticks: o sea, portar `TimeTicks`, `TimeInterval` y `TimeFormat` a JavaScript.

Todo el JavaScript del zoom son **~60 líneas y 0,7 kB gzip**, y no es más que aritmética sobre porcentajes.

## El estado vive en Livewire, y eso no es un detalle

Sale gratis:

- **Sobrevive al morph** sin ningún hook. Livewire no puede borrar lo que él mismo renderiza.
- **Se comparte por URL.** Un `#[Url]` y ya: ese zoom es un enlace.
- **Se testea sin navegador**, con `Livewire::test()`.

Un zoom que viviera en Alpine necesitaría un hook del morph para no perderse, no se podría enlazar, y no se podría testear.

## El eje Y se reescala sobre lo que se ve

Ampliar una semana de un año y dejar el eje Y llegando al máximo **anual** deja el gráfico aplastado contra el suelo: el pico ya no está en pantalla, pero el eje sigue reservándole sitio.

Así que el dominio del eje Y se calcula sobre **las filas visibles**. Es lo que ECharts llama `filterMode: 'filter'`, y es *la* decisión de diseño del zoom, no un detalle.

Las filas de fuera **no se borran**: se quedan con una posición negativa o mayor que 100, para que el trazo siga **saliendo por el borde** en vez de cortarse en seco contra él. El recorte es visual (`clip-path`), no de dato.

## Los controles son botones de verdad

Chips de `+`, `−` y `Restablecer`, y la ventana del contexto es un `<button>` que entra en el tab order y se desplaza con las flechas.

**Ni ECharts, ni uPlot, ni Highcharts tienen un zoom que se pueda usar con el teclado** (el mantenedor de uPlot cerró la puerta a la accesibilidad por escrito). El arrastre es el atajo para quien tiene ratón, no el mecanismo.

## El slider de contexto es gratis

Un segundo `<svg>` con la serie entera. Y no es una forma de hablar: **un `<path>` son 17 nodos de DOM pase lo que pase** — con diez puntos o con diez mil. En una arquitectura que dibuja en el servidor, un gráfico de contexto no cuesta nada. En una de canvas es un segundo motor de dibujo.

Se apaga con `:slider="false"`.

## Props

| Prop | Dónde | Tipo | Por defecto | Qué hace |
|---|---|---|---|---|
| `window` | `<x-kore::chart>` | array\|null | `null` | El tramo visible, en % del dominio completo |
| `wire:model` | `<x-kore::chart.zoom>` | string | — | **Obligatorio.** La propiedad Livewire donde vive la ventana |
| `slider` | `<x-kore::chart.zoom>` | bool | `true` | El mini-gráfico de contexto |
| `show` | `<x-kore::chart.zoom>` | bool | `true` | Apaga el zoom |

## Gestos

| Gesto | Qué hace |
|---|---|
| Arrastrar sobre el gráfico | Amplía ese tramo |
| Arrastrar la ventana del contexto | Se desplaza (pan) |
| Arrastrar un asa del contexto | Estira o encoge la ventana |
| Clic en el contexto, fuera de la ventana | Lleva la ventana ahí |
| Doble clic sobre el gráfico | Restablece |
| `←` `→` sobre la ventana (con el foco) | Se desplaza |
| `Inicio` sobre la ventana | Restablece |

## Lo que NO hay, y no es un olvido

**Zoom continuo con rueda o pinch.**

Una rueda dispara ~50 eventos por segundo, y **cada uno cambia los ticks del eje**. Hacerlo bien exige portar `Ticks`, `Scales`, `Path` y `Format` a JavaScript: unos 5–6 kB gzip y, sobre todo, **dos implementaciones de la geometría que hay que mantener idénticas para siempre**.

Con una trampa concreta: `Format` tendría que producir *byte a byte* lo mismo en el ICU de PHP y en el de JavaScript — y ese problema ya nos mordió una vez, con los espacios raros que ICU mete antes del «€» (mira el array de `TextWidth`).

Es exactamente la deuda que esta arquitectura se eligió para no contraer.

> **El pan sí cabe, y por un motivo geométrico.** Desplazar una ventana no deforma nada: ni el trazo, ni el ancho de una barra, ni un radio, ni una etiqueta. Sólo mueve. Ampliarla, sí deforma. Por eso durante el arrastre la ventana se mueve en el cliente (es compositor puro, sale gratis) y al soltar se pide **un** round-trip. Es el modelo de un mapa: el mosaico se escala mientras arrastras, y se redibuja nítido al soltar.
