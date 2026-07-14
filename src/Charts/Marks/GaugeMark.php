<?php

namespace KoreUi\Charts\Marks;

/**
 * Un gauge: UN número, contra un objetivo, y con rangos de color.
 *
 * ## ⚠️ Sin rangos de color, un gauge no es un gauge
 *
 * Es un *stat tile* con un anillo decorativo — y para eso ya hay un stat tile, que ocupa menos y
 * se lee antes. Lo que justifica el arco es el CONTEXTO: «73 % de CPU» no dice gran cosa; «73 %, en
 * la banda amarilla, cerca de la roja» sí. Los `thresholds` son esa banda. Sin ellos, el gauge se
 * pinta —no se prohíbe— pero la documentación te dice que probablemente quieres otra cosa.
 *
 * ## Reutiliza el donut
 *
 * Vive en un SVG **cuadrado y con escalado uniforme**, como el donut: un arco se deformaría con
 * `preserveAspectRatio="none"`. Y la trigonometría del arco ya estaba en `Arc`.
 */
final class GaugeMark extends Mark
{
    /** El dominio del gauge. Un porcentaje va de 0 a 100; un SLA, de donde tú digas. */
    public float $min = 0.0;

    public float $max = 100.0;

    /**
     * Cuántos grados abarca el arco. 270 es el clásico de velocímetro (hueco abajo); 180, un
     * semicírculo. Nunca 360 — eso es un donut, no un gauge.
     */
    public float $sweep = 270.0;

    /**
     * Los rangos de color, como `[cota => token]`: `[60 => 'success', 85 => 'warning', 100 => 'destructive']`
     * son tres bandas (0–60 verde, 60–85 ámbar, 85–100 rojo). El valor se pinta con el color de la
     * banda en la que cae. Vacío = decorativo (ver arriba).
     *
     * @var array<int|float, string>
     */
    public array $thresholds = [];

    /** Un texto bajo el número: «CPU», «SLA»… Lo que el número significa. */
    public ?string $caption = null;

    /** El grosor del arco no representa un valor: es cosmético, no exige el cero. */
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
        return 'gauge';
    }

    public function withRange(float $min, float $max): static
    {
        $this->min = $min;
        $this->max = $max > $min ? $max : $min + 1.0;

        return $this;
    }

    public function withSweep(float $sweep): static
    {
        // Menos de un pelín no es un arco; 360 o más es un donut. Se queda en medio.
        $this->sweep = max(10.0, min(350.0, $sweep));

        return $this;
    }

    public function withThresholds(array $thresholds): static
    {
        ksort($thresholds);
        $this->thresholds = $thresholds;

        return $this;
    }

    public function withCaption(?string $caption): static
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * El token de color de un valor: el de la banda en la que cae.
     *
     * Sin bandas, el color de la paleta. Con bandas, la primera cuya cota no se ha pasado — y si
     * se pasan todas, la última (el valor se salió por arriba, y eso normalmente es lo peor).
     */
    public function colorFor(float $value): ?string
    {
        if ($this->thresholds === []) {
            return null;
        }

        foreach ($this->thresholds as $upTo => $token) {
            if ($value <= $upTo) {
                return $token;
            }
        }

        return end($this->thresholds) ?: null;
    }
}
