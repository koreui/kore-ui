# Cascada (waterfall)

El puente entre un valor inicial y uno final: **cada barra flota sobre la suma de las anteriores**. Enseña de un vistazo de dónde salió un beneficio, o qué se comió un presupuesto.

## Uso

```blade
<x-kore::chart :data="$pyl" x="concepto">
    <x-kore::chart.waterfall y="importe" total="total" />
    <x-kore::chart.axis-y :ticks="5" format="compact" />
    <x-kore::chart.axis-x />
</x-kore::chart>
```

```php
$pyl = [
    ['concepto' => 'Ingresos',        'importe' =>  128000, 'total' => true],
    ['concepto' => 'Coste de ventas', 'importe' =>  -47000, 'total' => false],
    ['concepto' => 'Marketing',       'importe' =>  -18500, 'total' => false],
    ['concepto' => 'Beneficio',       'importe' =>       0, 'total' => true],   // se calcula solo
];
```

## Cómo reutiliza lo que ya había

Una cascada **es un apilado de una sola serie con la base moviéndose por fila**. La barra flotante ya la calculaba `layoutBars()` para las pilas —`at(base + valor)`—; aquí la base es la suma corrida en vez del segmento de debajo. No hay geometría nueva.

## Saltos y totales

- Una fila normal es un **salto**: la barra va del acumulado al acumulado + el valor. Sube (verde) o baja (rojo).
- Una fila marcada como **total** es un descansillo: la barra va del cero al acumulado, en neutro, y no mueve la suma.

La columna `total` es booleana — las filas ciertas son totales.

**Los totales se calculan solos si los dejas vacíos.** Deja el importe del beneficio final en `0` y sale la suma de todo lo anterior; no hay que repetir la cuenta en el dato. Y si traes tú el valor (por ejemplo, el saldo de apertura), se usa ése — así el primer total no queda clavado en cero.

## El color codifica polaridad, no identidad

Verde para lo que suma, rojo para lo que resta, neutro para los totales. **Es el único sitio del módulo donde una serie usa los tokens semánticos**, y es legítimo: `--kore-success` significa «esto suma», que es exactamente lo que dice una barra que sube. No es como pintar la serie 2 de verde por gusto.

## Props

| Prop | Tipo | Por defecto | Qué hace |
|---|---|---|---|
| `y` | string | — | La columna del salto de cada etapa |
| `total` | string | `null` | La columna booleana que marca los totales |
| `connectors` | bool | `true` | Las líneas que enlazan una barra con la siguiente |
| `show` | bool | `true` | Apaga la marca |

## Una cascada no comparte gráfico

Cada barra flota sobre la suma de las anteriores, así que superponerle una línea o unas barras normales mezclaría dos sistemas de coordenadas. Como el donut, vive sola — y si le pones otra marca, **lanza**.
