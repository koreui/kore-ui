# Chart Area

El área bajo una línea.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.area y="ingresos" label="Ingresos" curve="monotone" />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `y` | `string` | — | La clave del valor en cada fila |
| `label` | `string` | `null` | El nombre de la serie |
| `color` | `string` | `null` | Un token o un color CSS |
| `curve` | `string` | `linear` | `linear`, `monotone` o `step` |

## Por qué el área sí llega al cero

A diferencia de una línea, el área es una **superficie**, y su tamaño se lee como magnitud. Un área que arranca en 40 dibuja una mancha enorme para una variación mínima. Por eso el eje incluye el cero.

Si quieres el área y la línea encima, son dos marcas:

```blade
<x-kore::chart.area y="ingresos" curve="monotone" />
<x-kore::chart.line y="ingresos" curve="monotone" />
```

## Los huecos

Un `null` parte el área en dos. No se rellena el vacío.
