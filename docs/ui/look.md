# Aspecto: borde, sombra, relleno y densidad

Las superficies de la librería —tarjetas, tablas, métricas, listas de
descripción, la barra superior— pintan un marco. Estas cuatro banderas deciden
cuánto marco:

| Bandera | Qué controla |
|---|---|
| `bordered` | el borde. En una tabla, además, las líneas verticales entre columnas |
| `shadow` | la sombra de la superficie |
| `padding` | el relleno interior |
| `compact` | filas más apretadas |

```blade
<x-kore::card :shadow="false">Sin sombra, esta tarjeta y solo esta.</x-kore::card>
<x-kore::table :headers="$cabeceras" :rows="$filas" bordered compact />
```

## Cambiarlas de una vez

Repetir `:shadow="false"` etiqueta por etiqueta no es forma de tener un diseño
plano. En `config/kore-ui.php`:

```php
'ui' => [
    // Toda la librería sin sombras…
    'look' => [
        'shadow' => false,
    ],

    // …salvo las tarjetas, que las conservan
    'card' => [
        'shadow' => true,
    ],
],
```

De fábrica todas valen `null`, que significa «no opino»: cada componente
conserva el aspecto que ya tenía. La cascada va de más fuerte a más débil:

1. **El prop de la etiqueta.** `<x-kore::card :shadow="false">` manda siempre.
2. **`ui.<componente>.<bandera>`** — el ajuste de ese componente.
3. **`ui.look.<bandera>`** — el ajuste de toda la librería.
4. **El defecto del componente**, que es lo que hace cuando nadie opina.

## Qué acepta cada componente

| Componente | `bordered` | `shadow` | `padding` | `compact` | Defectos |
|---|:--:|:--:|:--:|:--:|---|
| `card` | ✓ | ✓ | ✓ | — | borde, sombra y relleno |
| `stats` | ✓ | ✓ | ✓ | — | borde y relleno, sin sombra |
| `table` | ✓ | ✓ | — | ✓ | ni bordes internos ni sombra |
| `descriptions` | ✓ | — | — | — | sin borde |
| `navbar` | ✓ | — | — | — | con borde inferior |

La barra superior lee su sección en `shell.navbar`, no en `ui`, porque es del
shell; su borde entra igual en la cascada.

`density` es más específico que `compact`: si la etiqueta dice
`density="relaxed"`, eso es lo que se aplica aunque `compact` esté encendido en
la configuración.

## Las siluetas heredan el marco

`<x-kore::card skeleton>` dibuja su silueta con el mismo marco que tendría la
tarjeta: si la tarjeta no lleva sombra, la silueta tampoco. Es lo que evita que
la página salte al llegar los datos —ver [skeleton](skeleton.md#siluetas-de-componente)—.

## Detalle de implementación

La cascada vive en `KoreUi\Core\Support\Look`. Una bandera que no esté en
`Look::BANDERAS` lanza excepción en vez de resolverse a su defecto en silencio:
una errata en el nombre dejaría el interruptor apagado para siempre sin que
nadie se entere, que es exactamente lo que le pasaba al `bordered` de la tabla
antes de esto.
