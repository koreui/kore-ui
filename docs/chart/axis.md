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
| `hide` | `bool` | `false` | Oculta el eje |

## Props de `<x-kore::chart.axis-x>`

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `max-labels` | `int` | `12` | Cuántas etiquetas como mucho; el resto se saltan |
| `hide` | `bool` | `false` | Oculta el eje |

## `ticks` es una pista, no un contrato

Si pides 5, te pueden salir 7. Es deliberado: el algoritmo prioriza que los valores sean **redondos** (1.000, 2.000, 3.000) sobre que sean exactamente los que pediste. Ningún algoritmo puede darte las dos cosas, y un eje que dice "1.224" está roto.

Es el mismo algoritmo que usa d3, y da exactamente los mismos ticks.

## Las etiquetas del eje X no se rotan

Cuando no caben, se **saltan** (una de cada N). No se giran: una etiqueta rotada no ocupa sitio en el layout, así que la fila del eje no crecería y el texto se saldría de la caja.

## Limitaciones

El eje X es **categórico**. Las fechas se pre-formatean en PHP y entran como categorías. Una escala temporal de verdad —con ticks que caigan en fronteras de mes o trimestre— es otro algoritmo entero y no está en esta versión.
