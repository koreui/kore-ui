<?php

namespace KoreUi\Core\Support;

use Illuminate\View\ComponentAttributeBag;

/**
 * Si un componente se mueve o no, y cómo se le dice al navegador.
 *
 * **Todo el movimiento de la librería lee tres tokens** —`--kore-duration-fast`,
 * `-base` y `-slow`— además de los del sidebar, los gráficos y el resorte del
 * toast. Apagarlo es ponerlos a cero, y eso lo hace una sola regla de
 * `kore-theme.css` sobre `[data-kore-motion="off"]`. Esta clase solo decide
 * cuándo hay que escribir ese atributo.
 *
 * **La cascada**, de más fuerte a más débil:
 *
 * 1. El prop de la etiqueta: `<x-kore::dropdown :animated="false">`.
 * 2. `config('kore-ui.ui.motion')` — toda la aplicación.
 * 3. Encendido.
 *
 * Hay un tercer nivel que no pasa por aquí porque no necesita PHP: cualquier
 * contenedor con `data-kore-motion="off"` apaga lo que tenga dentro.
 *
 * ```blade
 * <x-kore::select :animated="false" … />
 *
 * <section data-kore-motion="off">
 *     … un formulario entero sin transiciones …
 * </section>
 * ```
 *
 * **Lo que NO se apaga:** el spinner. Es la única señal de que algo está
 * pasando; quieto, es un círculo que no dice nada.
 */
final class Motion
{
    public const ATRIBUTO = 'data-kore-motion';

    /** Si el componente debe moverse. `null` en el prop es «no dice nada». */
    public static function activo(?bool $prop = null): bool
    {
        if ($prop !== null) {
            return $prop;
        }

        return (bool) config('kore-ui.ui.motion', true);
    }

    /**
     * El bag de atributos del componente, con la marca puesta si hace falta.
     *
     * Solo se escribe cuando el PROP lo pide. El ajuste global no marca cada
     * componente —serían cientos de atributos idénticos en una página—: lo
     * pone `@koreScripts` una vez, en `<html>`.
     */
    public static function marcar(ComponentAttributeBag $atributos, ?bool $prop): ComponentAttributeBag
    {
        if ($prop === false) {
            return $atributos->merge([self::ATRIBUTO => 'off']);
        }

        return $atributos;
    }
}
