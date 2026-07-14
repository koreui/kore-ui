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

## El área ya dibuja su propio trazo

No hace falta añadirle una marca `line` encima. Si lo haces, el gráfico tiene **dos series** sobre los mismos datos: gastas un color de la paleta, y el tooltip enseña la misma cifra dos veces.

```blade
{{-- Basta con esto: el borde superior del área es su trazo. --}}
<x-kore::chart.area y="ingresos" curve="monotone" />
```

## Los huecos

Un `null` parte el área en dos. No se rellena el vacío.
