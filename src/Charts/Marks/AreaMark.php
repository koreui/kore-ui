<?php

namespace KoreUi\Charts\Marks;

use KoreUi\Charts\Path;

/**
 * El área bajo una línea.
 *
 * Sí exige el cero: el área es una superficie, y su tamaño se lee como magnitud. Un área
 * que empieza en 40 dibuja una mancha enorme para una variación mínima.
 */
final class AreaMark extends Mark
{
    public string $curve = Path::LINEAR;

    public function requiresZero(): bool
    {
        return true;
    }

    public function medium(): string
    {
        return self::SVG;
    }

    public function type(): string
    {
        return 'area';
    }

    public function withCurve(?string $curve): self
    {
        $this->curve = in_array($curve, [Path::LINEAR, Path::MONOTONE, Path::STEP], true)
            ? $curve
            : Path::LINEAR;

        return $this;
    }
}
