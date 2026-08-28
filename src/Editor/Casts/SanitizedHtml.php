<?php

namespace KoreUi\Editor\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use KoreUi\Editor\HtmlSanitizer;

/**
 * Limpia el HTML del editor al guardarlo, sin que nadie tenga que acordarse.
 *
 * El saneado del navegador no es una frontera: el valor viaja por `wire:model` y
 * cualquiera puede mandar por ese hilo lo que quiera. Llamar a
 * `HtmlSanitizer::limpiar()` a mano funciona hasta el día en que alguien guarda
 * desde otro sitio —un comando, un import, una API— y se le olvida. Puesto en el
 * modelo, el agujero deja de depender de la memoria de nadie.
 *
 * ```php
 * protected function casts(): array
 * {
 *     return ['cuerpo' => SanitizedHtml::class];
 * }
 * ```
 *
 * Limpia al ESCRIBIR, no al leer: lo que ya está guardado no se toca en cada
 * consulta —sería pagar el saneado en cada lectura de cada fila— y lo que entra
 * queda limpio de una vez. Para arreglar lo ya guardado, una migración.
 */
class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return HtmlSanitizer::limpiar((string) $value);
    }
}
