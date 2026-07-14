<?php

namespace KoreUi\Charts;

use KoreUi\Charts\Exceptions\OrphanMarkException;

/**
 * El puente entre `<x-kore::chart>` y sus marcas.
 *
 * **Por qué existe.** Las marcas no pueden dibujarse a sí mismas: el dominio del eje es la
 * unión de TODAS las series, así que nadie puede calcular una coordenada hasta que la
 * última marca se ha registrado. Y Blade renderiza el contenido de un slot ANTES que la
 * plantilla que lo contiene. Así que las marcas se limitan a apuntarse aquí, sin emitir ni
 * un byte, y el padre dibuja el gráfico entero cuando ya las tiene todas.
 *
 * **Por qué es una PILA y no un registro plano** (que es lo que hace `ShellContext`). Un
 * sidebar huérfano es un absurdo que nadie escribe. Una marca huérfana, en cambio, es el
 * caso normal: un `<x-kore::chart.bar>` mal cerrado, un copy-paste, una marca dentro de un
 * `@include`. Con un registro plano, esa marca huérfana **se la comería el siguiente
 * gráfico de la página**: aparecería una serie fantasma, con un color robado de la paleta,
 * sin ningún error. Es exactamente el bug que Filament lleva años sin poder reproducir
 * («los colores se intercambian solos», #11515). Con una pila, una marca sin gráfico lanza
 * una excepción que dice qué pasa y dónde.
 *
 * Se enlaza como `scoped()`, no `singleton()`: con Octane el contenedor se reutiliza entre
 * peticiones, y el registro de una petición no puede filtrarse a la siguiente.
 */
final class ChartContext
{
    /** @var list<ChartFrame> */
    private array $stack = [];

    private int $counter = 0;

    /**
     * Un id determinista, y no es un detalle estético.
     *
     * Con `uniqid()` el id cambiaría en cada render: el morph de Livewire vería un nodo
     * distinto, lo reemplazaría entero, y el degradado del área parpadearía en cada
     * actualización. Además, dos gráficos con el mismo id de `<linearGradient>` colisionan
     * y `url(#grad)` resuelve siempre al primero.
     */
    public function nextId(): string
    {
        return 'kore-chart-'.(++$this->counter);
    }

    public function open(ChartFrame $frame): ChartFrame
    {
        $this->stack[] = $frame;

        return $frame;
    }

    /** El gráfico que se está construyendo ahora mismo. */
    public function current(string $mark = 'marca'): ChartFrame
    {
        if ($this->stack === []) {
            throw new OrphanMarkException(
                "koreUi: <x-kore::chart.{$mark}> tiene que ir DENTRO de un <x-kore::chart>. "
                ."Una marca suelta no sabe contra qué escala dibujarse, así que no se dibuja: "
                ."si se la quedara el siguiente gráfico de la página, aparecería una serie "
                ."fantasma con un color robado y nadie sabría de dónde ha salido."
            );
        }

        return $this->stack[count($this->stack) - 1];
    }

    /** Cierra el gráfico y devuelve todo lo que se registró en él. */
    public function close(): ChartFrame
    {
        if ($this->stack === []) {
            throw new OrphanMarkException('koreUi: se ha intentado cerrar un gráfico que no estaba abierto.');
        }

        return array_pop($this->stack);
    }

    public function depth(): int
    {
        return count($this->stack);
    }
}
