<?php

namespace KoreUi\DataTable\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Construcción de cláusulas LIKE con los comodines del usuario escapados.
 *
 * Un `%` o un `_` escritos por quien busca deben tratarse como texto literal:
 * si pasan crudos al patrón, cambian el significado de la consulta y un simple
 * "%%%%" fuerza un escaneo completo de la tabla.
 *
 * El carácter de escape se declara explícitamente en el SQL porque el valor por
 * defecto NO es el mismo en todos los motores (MySQL asume `\`, PostgreSQL y
 * SQLite no asumen ninguno), así que sin cláusula ESCAPE el mismo término da
 * resultados distintos según la base de datos.
 */
class LikePattern
{
    /**
     * Envuelve el término en comodines, escapando los que traiga dentro.
     */
    public static function contains(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    /**
     * Añade `columna LIKE ? ESCAPE '\'` a la consulta.
     *
     * $column debe venir ya validado por quien llama: aquí solo se entrecomilla
     * con el grammar, no se comprueba que sea un nombre de columna legítimo.
     */
    public static function where(Builder $query, string $column, string $pattern, bool $or = false): void
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);
        $sql     = "{$wrapped} LIKE ? ESCAPE '\\'";

        $or
            ? $query->orWhereRaw($sql, [$pattern])
            : $query->whereRaw($sql, [$pattern]);
    }
}
