<?php

namespace KoreUi\Charts\Marks;

/**
 * Una cascada (waterfall): el puente entre un valor inicial y uno final.
 *
 * Cada categoría es un salto —una subida o una bajada— y la barra **flota**: empieza donde acabó
 * la anterior. Es lo que enseña de un vistazo de dónde salió un beneficio, o qué se comió un
 * presupuesto.
 *
 * ## Por qué reutiliza casi todo
 *
 * Un waterfall **es un apilado de una sola serie con la base moviéndose por fila**. `layoutBars()`
 * ya calculaba `$base` y `$top = at($base + $value)` para las pilas; aquí la base es la suma
 * corrida en vez del segmento de debajo. La geometría de la barra flotante ya estaba escrita.
 *
 * ## El color codifica POLARIDAD, no identidad
 *
 * Una subida va en verde (`--kore-success`), una bajada en rojo (`--kore-destructive`). Es el
 * único sitio del módulo donde se usan los tokens semánticos para una serie, y es legítimo:
 * `--kore-success` significa «esto suma», que es exactamente lo que dice una barra que sube. No es
 * como pintar la serie 2 de verde por gusto.
 */
final class WaterfallMark extends Mark
{
    /**
     * El nombre de una columna booleana: las filas donde sea cierta son TOTALES.
     *
     * Un total no es un salto: es un descansillo. Su barra va del cero hasta la suma acumulada, y
     * NO mueve la suma corrida — es una foto del acumulado hasta ese punto. `null` = no hay totales
     * (una cascada puramente relativa).
     */
    public ?string $total = null;

    /** Las líneas finas que enlazan el final de una barra con el principio de la siguiente. */
    public bool $connectors = true;

    /** El cero es la línea de flotación: la altura de una barra ES su salto. */
    public function requiresZero(): bool
    {
        return true;
    }

    /** Como las barras: cajas HTML, no trazo. Un <div> con border-radius que se clampa solo. */
    public function medium(): string
    {
        return self::HTML;
    }

    public function type(): string
    {
        return 'waterfall';
    }

    public function withTotal(?string $total): static
    {
        $this->total = $total !== '' ? $total : null;

        return $this;
    }

    public function withConnectors(bool $connectors): static
    {
        $this->connectors = $connectors;

        return $this;
    }
}
