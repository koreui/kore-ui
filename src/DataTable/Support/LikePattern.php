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
 *
 * **Por qué el escape NO es una barra invertida.** Lo era, y rompía MySQL:
 * `ESCAPE '\'` deja el literal sin cerrar, porque dentro de una cadena la barra
 * escapa la comilla siguiente. Reproducido en MySQL 9.6 con el `sql_mode` por
 * defecto: `ERROR 1064 (42000) ... near ''\''`, y con él la búsqueda global de
 * toda tabla con columnas `searchable`.
 *
 * Duplicarla —`ESCAPE '\\'`— arregla MySQL y rompe los otros dos: SQLite
 * responde «ESCAPE expression must be a single character», y PostgreSQL hace lo
 * mismo con `standard_conforming_strings` activo, que es el valor por defecto
 * desde la 9.1. Sería cambiar un motor roto por dos.
 *
 * La salida es no usar un carácter que signifique algo dentro de un literal.
 * `!` no lo significa en ninguno de los tres, así que la misma cadena vale para
 * todos. Ver `tests/DataTable/LikePatternTest.php`.
 */
class LikePattern
{
    /**
     * El carácter que marca «lo siguiente es texto, no un comodín».
     *
     * Cualquiera sin significado dentro de un literal SQL sirve; lo que no vale
     * es la barra invertida. Ver la nota de la clase.
     */
    public const ESCAPE = '!';

    /**
     * Envuelve el término en comodines, escapando los que traiga dentro.
     */
    public static function contains(string $term): string
    {
        return '%'.static::escapar($term).'%';
    }

    /**
     * Neutraliza los comodines y el propio carácter de escape.
     *
     * El orden importa: el carácter de escape va PRIMERO, o los que añaden los
     * reemplazos siguientes se escaparían a su vez y el patrón dejaría de
     * corresponder al término.
     */
    public static function escapar(string $term): string
    {
        return str_replace(
            [static::ESCAPE, '%', '_'],
            [static::ESCAPE.static::ESCAPE, static::ESCAPE.'%', static::ESCAPE.'_'],
            $term
        );
    }

    /**
     * Añade `columna LIKE ? ESCAPE '!'` a la consulta.
     *
     * $column debe venir ya validado por quien llama: aquí solo se entrecomilla
     * con el grammar, no se comprueba que sea un nombre de columna legítimo.
     */
    public static function where(Builder $query, string $column, string $pattern, bool $or = false): void
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);
        $sql = "{$wrapped} LIKE ? ESCAPE '".static::ESCAPE."'";

        $or
            ? $query->orWhereRaw($sql, [$pattern])
            : $query->whereRaw($sql, [$pattern]);
    }
}
