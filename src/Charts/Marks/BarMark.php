<?php

namespace KoreUi\Charts\Marks;

/**
 * Una barra.
 *
 * Es HTML, no SVG, y es una decisión deliberada: un <rect rx="4"> dentro de un viewBox con
 * escalado no uniforme tendría las esquinas ELÍPTICAS, y el servidor no puede compensarlo
 * porque no conoce la escala en píxeles. Un <div> con border-radius se clampa solo, tiene
 * :hover y :focus nativos, y es enfocable con el teclado.
 */
final class BarMark extends Mark
{
    /** El cero es obligatorio: la longitud de la barra ES el valor. */
    public function requiresZero(): bool
    {
        return true;
    }

    public function medium(): string
    {
        return self::HTML;
    }

    public function type(): string
    {
        return 'bar';
    }

    public function isStacked(): bool
    {
        return $this->stack !== null;
    }
}
