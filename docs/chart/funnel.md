# Embudo (funnel)

Cuánta gente sobrevive cada paso de un proceso. Cada etapa es un trapecio más estrecho que el anterior: **el estrechamiento es la caída**.

## Uso

```blade
<x-kore::chart :data="$conversion" x="etapa">
    <x-kore::chart.funnel y="usuarios" />
</x-kore::chart>
```

```php
$conversion = [
    ['etapa' => 'Visitas',   'usuarios' => 12000],
    ['etapa' => 'Registros', 'usuarios' =>  4800],
    ['etapa' => 'Carrito',   'usuarios' =>  1500],
    ['etapa' => 'Compra',    'usuarios' =>   620],
];
```

**El orden de las filas es el orden del embudo.** A diferencia de un eje temporal, un embudo *no* ordena por valor: la secuencia la pones tú.

## Los números

Al lado de cada etapa van tres cosas:

- **Cuántos son** — el valor.
- **La conversión** — qué porcentaje queda del primero. De 12.000 a 620 es un 5,2 %.
- **La caída** — cuánto se pierde en *ese* paso, en rojo. De 4.800 a 1.500 es un −68,8 %; **no** el −87,5 % acumulado. Es la conversión paso a paso, que es lo que se acciona: te dice en qué escalón se te va la gente.

## El color es ORDINAL, no categórico

Las etapas van **en orden**, y cambiar el orden cambia el significado. Por eso el color sale de la rampa ordinal (`--kore-ord-*`), no de la categórica: la categórica dice «estas cosas son distintas», la ordinal dice «estas cosas van en esta secuencia».

Y aquí el color **no lleva el peso de la información** —eso ya lo hace la geometría, el ancho del trapecio—: sólo dice «vas por aquí». La rampa se reparte entre las etapas que haya: la primera clara, la última oscura.

## Props

| Prop | Tipo | Por defecto | Qué hace |
|---|---|---|---|
| `y` | string | — | La columna del valor de cada etapa |
| `show` | bool | `true` | Apaga la marca |

## Un embudo no comparte gráfico

Enseña las etapas de un proceso, una debajo de otra, sin ejes. Como el donut o el gauge, vive solo — y si le pones otra marca, **lanza**.
