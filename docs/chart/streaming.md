# Datos en vivo

**El morph de Livewire ya *es* la actualización.** Lo que hay que construir no es refrescar: es saber **cuándo no hacerlo**.

## Uso

```php
class Monitor extends Component
{
    public array $muestras = [];

    /** Un punto nuevo, y el más viejo se cae. Eso es toda la ventana deslizante. */
    public function tick(): void
    {
        $this->muestras[] = $this->leer();
        $this->muestras = array_slice($this->muestras, -40);
    }
}
```

```blade
<x-kore::chart :data="$this->serie" x="medido_en">
    <x-kore::chart.line y="cpu" curve="monotone" dots />

    <x-kore::chart.axis-y :min="0" :max="100" />
    <x-kore::chart.tooltip />

    <x-kore::chart.stream every="2s" call="tick" />
</x-kore::chart>
```

Y ya está.

## Lo que NO hay aquí

**Ni `wire:ignore`, ni `chart.update()`, ni una instancia de JavaScript que proteger.**

El morph cambia el atributo `d` del `<path>` **sin recrear el nodo**. No hay instancia que destruir, así que no hay nada que parpadee.

Eso no es un detalle: es el issue **#20103 de Filament** («el gráfico parpadea en cada polling»), abierto porque cada refresco destruye y recrea una instancia de Chart.js. **Nosotros no podemos tener ese bug.**

**Ni ring buffer, ni motor de streaming.** La ventana deslizante es un `array_slice`. El dato ya está en PHP: no hay que mandarlo a ninguna parte para recortarlo.

**Y ~25 líneas de JavaScript**: un temporizador y tres motivos para no dispararlo.

## Los tres momentos en que refrescar es hostil

Un `wire:poll` a secas refresca **siempre**. Por eso el refresco lo conduce el gráfico:

1. **Mientras lees un tooltip.** El dato se movería bajo el cursor y el número que estabas mirando cambiaría mientras lo miras. Pon el ratón encima y el gráfico se para; sácalo y se reanuda.

2. **Con la pestaña oculta.** Diez pestañas abiertas serían diez renders cada dos segundos en tu servidor, para nadie.

3. **Con el zoom puesto.** Has ampliado para mirar algo concreto: que se te mueva el suelo debajo es exactamente lo que no quieres.

## Fija el eje Y

En un gráfico en vivo esto deja de ser un lujo:

```blade
<x-kore::chart.axis-y :min="0" :max="100" />
```

Un eje que se reescala cada dos segundos porque el dato subió un punto es **ilegible**: la línea se queda quieta y lo que se mueve es el eje. Un porcentaje va de 0 a 100 y punto.

`min` y `max` son un **contrato**, no una sugerencia: no se redondea por debajo de lo que pides.

## Por qué la línea no se anima y las barras sí

Las barras y los puntos son `<div>` con la posición en custom properties: una transición CSS sobre `top`/`height` funciona en todas partes y hace lo correcto.

La línea es un `<path>`, y animarla exigiría `transition: d`. **Medido en los tres motores:**

| Motor | `CSS.supports('d')` | ¿Interpola de verdad? |
|---|---|---|
| Chromium | `true` | **No** — salto seco |
| Firefox | `true` | Sí |
| WebKit (Safari) | `false` | **Ni lo soporta** |

Y hay una razón mejor para no hacerlo aunque funcionara: en una ventana deslizante, interpolar `d` lleva el punto *i* hasta el valor del punto *i+1* — o sea, **la onda tiembla en el sitio en vez de desplazarse**. Es la animación equivocada. Un motor de canvas tiene el mismo problema y lo resuelve igual: redibujando.

La línea no necesita animación de todas formas: el morph cambia el `d` sin recrear el nodo, así que **no parpadea**.

> Todo esto se apaga con `:transition="false"`, y se respeta `prefers-reduced-motion` sin que hagas nada.

## El techo, y no es negociable

**Medido** en la demo (40 puntos, 2 series, un `<livewire>` con poco más que el gráfico):

| | Por refresco |
|---|---|
| HTML en crudo | **44 kB** |
| Comprimido (gzip) | **5,1 kB** |
| A 1 Hz | 5,0 kB/s **por cliente** |
| A 0,2 Hz (5 s, el defecto de Filament) | 1,0 kB/s por cliente |

Dos cosas de ahí:

**Un refresco es un round-trip completo de Livewire** —consulta, Blade, morph— y cuesta entre 30 y 80 ms de servidor más la red. Por debajo de **medio segundo** los refrescos se solapan, Livewire los encola, y el gráfico va cada vez más por detrás mientras el servidor arde. Por eso `every` **lanza** si le pides menos.

**Y el cuello de botella real no es ése: es el HTML.** Son **N renders para N clientes**. Cincuenta usuarios mirando el mismo panel a 1 Hz son cincuenta renders por segundo — de exactamente el mismo gráfico.

> **El techo honesto es 1 Hz con ≤ 200 puntos.** El defecto sensato son 5 s.
>
> **A 10 Hz no aguanta ninguna arquitectura que dibuje en el servidor. Ni ésta, ni ninguna.** Y decirlo aquí es más útil que dejar que lo descubras en producción.

## Si necesitas más de 1 Hz

Lo que hay que cambiar **no es el número**: es el **formato de cable**.

La tentación es «pues que dibuje el JavaScript» — o sea, portar `Ticks`, `Scales`, `Path` y `Format` a JS, y mantener dos implementaciones de la geometría idénticas para siempre. Es exactamente la deuda que esta arquitectura se eligió para no contraer.

Hay una tercera vía, y **no la hace nadie del ecosistema**:

> **El servidor sigue calculando la geometría, pero deja de mandar HTML y manda la geometría ya resuelta.**
>
> Un evento por broadcast (Reverb/Echo) con `{ d, xTicks, payload }`, y unas 30 líneas de JavaScript que hagan `path.setAttribute('d', …)`.
>
> - **Cero escalas en JavaScript.** Cero `Intl`. Cero paridad que mantener.
> - Y **una renderización para N clientes** en vez de N renders: es un broadcast, no un poll. A 1 Hz con 50 usuarios, `wire:poll` son 50 renders/s; un broadcast es **1**.
>
> La única concesión: ese `<path>` hay que protegerlo del morph (`wire:ignore` sobre esa capa), porque lo que escribe el cliente, el morph lo borra.

**No está implementado.** Está aquí porque es la salida correcta, no para fingir que ya existe.

## Props de `<x-kore::chart.stream>`

| Prop | Tipo | Por defecto | Qué hace |
|---|---|---|---|
| `every` | string\|int | `5s` | Cada cuánto refrescar. `«5s»`, `«500ms»` o milisegundos. **Lanza por debajo de 500 ms** |
| `call` | string | `null` | El método Livewire que trae el dato — como `wire:poll.5s="tick"`. Sin él, un `$refresh()` a secas |
| `transition` | bool | `true` | Las marcas se mueven a su sitio nuevo en vez de aparecer en él |
| `show` | bool | `true` | Apaga el refresco |
