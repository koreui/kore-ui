<?php

namespace KoreUi\Charts\Marks;

/**
 * Una marca: una capa de dibujo del gráfico.
 *
 * La idea viene de Observable Plot y es la razón de que la API sea componible: **no
 * existen los "tipos de gráfico"**. No hay un tipo `mixed`. Un gráfico de barras con una
 * línea de media encima no es un tipo especial: son dos marcas, una encima de otra. Eso es
 * todo lo que hay que entender del módulo.
 *
 * Cada marca declara dos cosas que el gráfico necesita saber ANTES de poder calcular nada,
 * y por eso el padre no puede dibujar hasta que todos los hijos se han registrado:
 *
 *  - `requiresZero()`: si la longitud de la marca representa el valor (una barra), el cero
 *    tiene que estar en el dominio o la marca miente sobre la proporción.
 *  - `medium()`: si se dibuja con un trazo (SVG) o con una caja (HTML). De ahí sale el
 *    orden de pintado.
 */
abstract class Mark
{
    public const SVG = 'svg';

    public const HTML = 'html';

    /** El slot de la paleta. Lo asigna el contexto por orden de registro, nunca por índice visible. */
    public int $slot = 1;

    public function __construct(
        public readonly string $field,
        public readonly ?string $label = null,
        public readonly ?string $color = null,
        public readonly ?string $stack = null,
    ) {}

    /** ¿La longitud de esta marca representa el valor? Entonces el cero es obligatorio. */
    abstract public function requiresZero(): bool;

    /** Trazo (SVG) o caja (HTML). Decide en qué capa vive. */
    abstract public function medium(): string;

    abstract public function type(): string;

    public function name(): string
    {
        return $this->label ?? $this->field;
    }

    public function withSlot(int $slot): static
    {
        $this->slot = $slot;

        return $this;
    }
}
