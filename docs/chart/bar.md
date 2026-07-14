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

## Por qué una barra siempre llega al cero

La **longitud** de la barra *es* el valor. Una barra que empieza en 40 miente sobre la proporción entre las barras. Por eso, en cuanto hay una barra, el eje Y incluye el cero — aunque el resto de series no lo necesiten.

## Las barras son HTML, no SVG

Un `<rect rx="4">` dentro del SVG tendría las esquinas **elípticas**, porque el gráfico se estira horizontalmente y el servidor no conoce la escala en píxeles para compensarlo. Un `<div>` con `border-radius` se clampa solo, tiene `:hover` nativo y se imprime bien.

## Limitaciones

**No se pueden apilar valores positivos y negativos en la misma pila.** El componente lanza una excepción en vez de dibujar algo que se lee mal.
