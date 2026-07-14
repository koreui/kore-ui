<?php

namespace KoreUi\Charts\Scales;

/**
 * Cómo el eje X convierte una fila del dato en una posición del gráfico.
 *
 * Hasta 1.6.0 esto no existía: `Plot::$x` era un `BandScale` concreto, y por tanto **la
 * posición de un punto era su ORDINAL en el array**, nunca su valor. Con categorías eso es lo
 * correcto («Ene», «Feb»…). Con fechas es una mentira: si a una serie diaria le faltan tres
 * días, el gráfico los cierra y dibuja una línea continua sobre un agujero que existió.
 *
 * Las tres implementaciones responden lo mismo con matemáticas distintas:
 *
 *  - `BandScale`      → la fila N cae en la banda N. Categorías.
 *  - `LinearXScale`   → la fila N cae donde diga su valor. Números.
 *  - `TimeScale`      → ídem, sobre el epoch. Fechas.
 */
interface XScale
{
    /**
     * Dónde cae el dato de la fila N, en el espacio 0–100.
     *
     * Devuelve `null` si la fila **no se puede colocar** (su X es nula en una escala continua).
     * No es lo mismo que la posición 0: una fila sin X no es un dato en el origen, es un hueco,
     * y el trazo tiene que partirse ahí.
     */
    public function positionAt(int $row): ?float;

    /**
     * El ancho de una barra en este eje, en el espacio 0–100.
     *
     * En una escala de bandas es la banda. En una continua no hay banda: es el **hueco mínimo
     * entre dos puntos consecutivos**, menos el padding. Sin eso, las barras de una serie con
     * fechas irregulares se solapan.
     */
    public function bandwidth(): float;

    /**
     * Los ticks del eje, ya etiquetados y colocados.
     *
     * `$count` es una PISTA, igual que en el eje Y: en una escala de bandas es el tope de
     * etiquetas que caben; en una continua, el número aproximado de ticks que se busca.
     *
     * `context` es la segunda línea de la etiqueta (el año, en un eje de días). Va aparte
     * porque no todos los ticks la llevan — solo el primero y aquellos en que cambia.
     *
     * `width` es lo que ocupa la etiqueta, en `ch`. El servidor no puede medir texto, pero sí
     * contarlo; con ese ancho, el CSS ACOTA la etiqueta dentro del área en vez de dejar que se
     * salga por el borde. Ver el trait `TickBox`.
     *
     * @return list<array{label: string, context: ?string, pos: float, width: float}>
     */
    public function ticks(int $count): array;

    /**
     * De una posición 0–100, de vuelta al dato.
     *
     * Es lo que hace que el zoom no necesite ni una escala en JavaScript: el cliente manda dos
     * porcentajes y el servidor los convierte en un dominio.
     */
    public function invert(float $position): mixed;

    /** Cuántas filas hay. */
    public function count(): int;
}
