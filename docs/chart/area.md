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
| `stack` | `string` | `null` | Las áreas con el mismo nombre se apilan. Ver abajo |
| `show` | `bool` | `true` | `:show="false"` la oculta **pero le reserva su color**. Ver abajo |

## Por qué el área sí llega al cero

A diferencia de una línea, el área es una **superficie**, y su tamaño se lee como magnitud. Un área que arranca en 40 dibuja una mancha enorme para una variación mínima. Por eso el eje incluye el cero.

## El área ya dibuja su propio trazo

No hace falta añadirle una marca `line` encima. Si lo haces, el gráfico tiene **dos series** sobre los mismos datos: gastas un color de la paleta, y el tooltip enseña la misma cifra dos veces.

```blade
{{-- Basta con esto: el borde superior del área es su trazo. --}}
<x-kore::chart.area y="ingresos" curve="monotone" />
```

## Área apilada

Varias áreas con el mismo `stack` se **apilan**: cada banda se apoya en la suma de las de debajo, no en el cero. La primera que declaras es la de abajo.

```blade
<x-kore::chart :data="$trafico" x="mes">
    <x-kore::chart.area y="organico" label="Orgánico" stack="canal" />
    <x-kore::chart.area y="pago"     label="De pago"  stack="canal" />
    <x-kore::chart.area y="social"   label="Social"   stack="canal" />
</x-kore::chart>
```

Sirve para leer **dos cosas a la vez**: la silueta de arriba es el **total**, y el grosor de cada franja, su **aportación**. El eje llega a la cima de la pila (la suma), no al mayor de los sumandos.

Es el mismo `stack` de las barras, extendido al área. Por dentro, la línea base de cada banda deja de ser plana y pasa a ser la curva acumulada de las de debajo (`Path::areaBetween`); las bandas apiladas se pintan más opacas que un área suelta, porque son el contenido y no un relleno tenue.

**Sin `stack`, las mismas áreas se superponen** translúcidas desde el cero. Es otra lectura —comparar formas en vez de sumar—, y ninguna es "la correcta": son dos preguntas distintas.

Funciona con `curve="monotone"`: cada borde se suaviza y la banda de arriba sigue apoyándose exactamente en la de abajo (la monótona no inventa un máximo que el dato no tiene). Un `null` en cualquiera de los dos bordes parte la banda.

## Los huecos

Un `null` parte el área en dos. No se rellena el vacío.
