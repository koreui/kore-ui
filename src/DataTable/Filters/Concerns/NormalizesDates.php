<?php

namespace KoreUi\DataTable\Filters\Concerns;

use Carbon\Carbon;
use Throwable;

/**
 * Normaliza a `Y-m-d` lo que llegue del cliente como fecha.
 *
 * `whereDate()` con un valor arbitrario no es inofensivo: en PostgreSQL comparar
 * una fecha con una cadena que no lo es aborta la consulta con un error de SQL.
 * Y aunque el datepicker mande siempre un formato correcto, `$filters` es una
 * propiedad pública y su contenido no está garantizado.
 */
trait NormalizesDates
{
    protected function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
