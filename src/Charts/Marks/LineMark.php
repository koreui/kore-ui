<?php

namespace KoreUi\Charts\Marks;

use KoreUi\Charts\Path;

/** Una línea. No exige el cero: forzarlo aplastaría la señal contra el techo. */
final class LineMark extends Mark
{
    public string $curve = Path::LINEAR;

    public bool $dots = false;

    public function requiresZero(): bool
    {
        return false;
    }

    public function medium(): string
    {
        return self::SVG;
    }

    public function type(): string
    {
        return 'line';
    }

    public function withCurve(?string $curve): self
    {
        $this->curve = in_array($curve, [Path::LINEAR, Path::MONOTONE, Path::STEP], true)
            ? $curve
            : Path::LINEAR;

        return $this;
    }

    /**
     * Los puntos son OPT-IN, y no por capricho: un <div> por punto escala 1:1 con el dato.
     * Medido: con 10.000 puntos, mover el crosshair cuesta 2,9 ms por frame y el HTML pesa
     * 1,4 MB. Sin puntos, el DOM se queda en 17 nodos pase lo que pase, porque el trazo es
     * UN SOLO nodo.
     */
    public function withDots(bool $dots): self
    {
        $this->dots = $dots;

        return $this;
    }
}
