# Chart Line

Una línea. No fuerza el cero en el eje: forzarlo aplastaría la señal contra el techo.

## Uso básico

```blade
<x-kore::chart :data="$ventas" x="mes">
    <x-kore::chart.line y="ingresos" label="Ingresos" curve="monotone" />
</x-kore::chart>
```

## Props

| Prop | Tipo | Default | Descripción |
|---|---|---|---|
| `y` | `string` | — | La clave del valor en cada fila |
| `label` | `string` | `null` | El nombre de la serie. Sin él, se usa `y` |
| `color` | `string` | `null` | Un token (`destructive`, `success`…) o un color CSS. Por defecto, el de la paleta |
| `curve` | `string` | `linear` | `linear`, `monotone` o `step` |
| `dots` | `bool` | `false` | Pintar un punto por dato |

## La curva `monotone`

Es una curva que **no inventa extremos**. Una spline normal puede dibujar un máximo entre dos puntos donde no hay ningún dato — y en un gráfico de negocio eso no es un problema estético, es un problema de honestidad. La monótona garantiza que entre dos puntos la curva no se sale del rango de esos dos puntos.

## Los huecos

Un `null` **no es un cero**: es "no hay dato". La línea se corta ahí, y no dibuja una caída al suelo que nunca ocurrió.

## Limitaciones

`dots` es un `<div>` por punto. Con 10.000 puntos, el HTML pesa 1,4 MB y mover el crosshair cuesta medio frame. Sin ellos, el trazo es **un solo nodo** y el número de datos le da igual al navegador.
