# Chart Axis

Los ejes: cuántos ticks y cómo se escriben.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.line y="ingresos" />
    <x-kore::chart.axis-y :ticks="5" format="currency" />
    <x-kore::chart.axis-x />
</x-kore::chart>
```

## Props de `<x-kore::chart.axis-y>`

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `ticks` | `int` | `5` | Cuántos ticks, **como pista** (ver abajo) |
| `format` | `string` | `number` | `number`, `currency`, `percent` o `compact` |
| `show` | `bool` | `true` | `:show="false"` apaga el eje (y su canaleta deja de reservar ancho) |

## Props de `<x-kore::chart.axis-x>`

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `scale` | `string` | `auto` | `auto`, `band` (categorías), `time` (fechas) o `linear` (números) |
| `timezone` | `string` | la del dato | Con qué zona horaria se lee la fecha |
| `ticks` | `int` | `6` | **Objetivo** de ticks en una escala continua (fechas o números) |
| `max-labels` | `int` | `12` | **Tope** de etiquetas en una escala de categorías; el resto se saltan |
| `show` | `bool` | `true` | `:show="false"` apaga el eje (y su canaleta deja de reservar ancho) |

## El eje X tiene tres formas, y elegir mal la escala cambia lo que el gráfico dice

- **`band`** — categorías. La fila N cae en la banda N. Es lo correcto para «Ene, Feb, Mar».
- **`time`** — fechas. Cada punto cae **donde le toca en el calendario**, así que los huecos se ven.
- **`linear`** — números. Lo que necesita un scatter.

`auto` detecta fechas y solo fechas. **Nunca promociona a `linear` por su cuenta**: unos años escritos como enteros (2022, 2023, 2024) son *categorías*, no una recta numérica, y colocarlos en una escala lineal le cambiaría el gráfico a quien no ha pedido nada.

⚠️ **Con `x="fecha"` y una cadena ya formateada, el gráfico solo puede colocar por ORDEN — y los huecos del calendario desaparecen.** Un sensor caído tres días se dibuja como si no se hubiera caído. Pasa objetos `DateTime` o `Carbon`. Ver [time-axis.md](time-axis.md).

## `ticks` y `max-labels` no son lo mismo

Y confundirlos sale caro:

- En una escala de **bandas**, `max-labels` es un **tope**: hay N categorías y se pintan como mucho ésas, saltando el resto.
- En una escala **continua**, `ticks` es un **objetivo**: no hay categorías que saltar, hay ticks que *elegir*. Pedir doce para un rango de una semana da uno cada doce horas, y se pisan unos con otros.

Por eso el defecto de una escala continua es más bajo (6).

## `ticks` es una pista, no un contrato

Si pides 5, te pueden salir 7. Es deliberado: el algoritmo prioriza que los valores sean **redondos** (1.000, 2.000, 3.000) sobre que sean exactamente los que pediste. Ningún algoritmo puede darte las dos cosas, y un eje que dice "1.224" está roto.

Es el mismo algoritmo que usa d3, y da exactamente los mismos ticks.

## Las etiquetas del eje X no se rotan

Cuando no caben, se **saltan** (una de cada N). No se giran: una etiqueta rotada no ocupa sitio en el layout, así que la fila del eje no crecería y el texto se saldría de la caja.

## Limitaciones

El eje X es **categórico**. Las fechas se pre-formatean en PHP y entran como categorías. Una escala temporal de verdad —con ticks que caigan en fronteras de mes o trimestre— es otro algoritmo entero y no está en esta versión.
