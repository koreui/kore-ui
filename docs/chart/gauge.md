# Gauge (radial)

Un número, contra un objetivo, y con rangos de color.

## Uso

```blade
<x-kore::chart :data="[['uso' => 73]]">
    <x-kore::chart.gauge
        y="uso"
        :thresholds="[60 => 'success', 85 => 'warning', 100 => 'destructive']"
        caption="Memoria" />
</x-kore::chart>
```

Un gauge enseña **un** número — el de la primera fila con dato.

## ⚠️ Sin rangos de color, un gauge no es un gauge

Es un **stat tile con un anillo decorativo** — y para eso ya hay un stat tile, que ocupa menos y se lee antes.

Lo que justifica el arco es el **contexto**: «73 % de memoria» no dice gran cosa; «73, en la banda ámbar, cerca de la roja» sí. Los `thresholds` son esa banda. El número y el arco se pintan con el color de la banda en la que cae el valor, y unos pellizcos marcan dónde empieza cada una.

No se prohíbe pintar un gauge sin rangos, pero probablemente quieres otra cosa.

## Los rangos

`thresholds` es un mapa `[cota => token]`:

```php
[60 => 'success', 85 => 'warning', 100 => 'destructive']
```

Son tres bandas: 0–60 verde, 60–85 ámbar, 85–100 rojo. El valor se pinta con el color de la banda en la que cae; si se sale por arriba, con la última (que suele ser lo peor).

Los tokens son los semánticos de kore (`success`, `warning`, `destructive`, `info`…). Es un uso legítimo: aquí el color **significa** un estado, no identifica una serie.

## El dominio no tiene por qué ser 0–100

Un SLA va de donde tú digas, y no pierde los decimales:

```blade
<x-kore::chart.gauge y="sla" :min="98" :max="100"
    :thresholds="[99 => 'destructive', 99.9 => 'warning', 100 => 'success']"
    caption="Disponibilidad %" />
```

## Reutiliza el donut

Vive en un SVG **cuadrado y con escalado uniforme**, como el donut: un arco se deformaría con `preserveAspectRatio="none"`. La trigonometría del arco ya estaba en `Arc`; un gauge es un arco **trazado** (con las puntas redondeadas), no un anillo relleno.

## Props

| Prop | Tipo | Por defecto | Qué hace |
|---|---|---|---|
| `y` | string | — | La columna del valor |
| `min` / `max` | número | `0` / `100` | El dominio del arco |
| `sweep` | número | `270` | Cuántos grados abarca. 270 = velocímetro, 180 = semicírculo |
| `thresholds` | array | `[]` | Los rangos de color, `[cota => token]` |
| `caption` | string | `null` | El texto bajo el número |
| `show` | bool | `true` | Apaga la marca |

## Un gauge no comparte gráfico

Enseña un número contra un objetivo, no una serie sobre unos ejes. Como el donut, vive solo — y si le pones otra marca, **lanza**.
