<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use KoreUi\DataTable\Support\LikePattern;

/**
 * El patrón LIKE, ejecutado de verdad contra la base de datos.
 *
 * Este archivo existe por un fallo que ningún aserto de cadenas habría cazado:
 * la cláusula se emitía como `ESCAPE '\'`, con una barra invertida, y en MySQL
 * eso deja el literal sin cerrar —la barra escapa la comilla siguiente—. La
 * consulta moría con `ERROR 1064 (42000)` y con ella **la búsqueda global de
 * toda tabla con columnas `searchable`**, más el filtro de texto.
 *
 * Dos cosas de aquel fallo dan forma a estos tests:
 *
 * 1. **No se veía en el CI** porque la suite corre contra SQLite, donde `'\'`
 *    es una barra literal y no pasa nada. Un paquete que dice soportar tres
 *    motores probaba uno.
 * 2. **El arreglo evidente empeoraba las cosas.** Duplicar la barra arregla
 *    MySQL y rompe SQLite —«ESCAPE expression must be a single character»— y
 *    PostgreSQL con `standard_conforming_strings` activo. Por eso el escape ya
 *    no es una barra: es un carácter que no significa nada dentro de un literal
 *    en ninguno de los tres.
 *
 * De ahí que estos tests **ejecuten la consulta** en vez de comparar el SQL
 * generado: lo que hay que comprobar es que la base de datos la acepta, y eso
 * solo lo dice la base de datos.
 */
beforeEach(function () {
    Schema::dropIfExists('busquedas');

    Schema::create('busquedas', function (Blueprint $tabla) {
        $tabla->id();
        $tabla->string('texto');
    });

    DB::table('busquedas')->insert([
        ['texto' => 'Cecilia Torralba'],
        ['texto' => '100% natural'],
        ['texto' => '100 natural'],
        ['texto' => 'a_b'],
        ['texto' => 'axb'],
        ['texto' => 'ruta\\con\\barras'],
        ['texto' => 'signo! de admiración'],
    ]);
});

/** El caso de todos los días: la consulta se ejecuta y encuentra. */
it('busca un término normal', function () {
    $encontrados = consultar(LikePattern::contains('Torralba'));

    expect($encontrados)->toBe(['Cecilia Torralba']);
});

/**
 * Un `%` escrito por quien busca es TEXTO, no un comodín. Si pasa crudo, «100%»
 * encontraría también «100 natural», y un «%%%%» fuerza un escaneo completo.
 */
it('trata el porcentaje del usuario como texto', function () {
    $encontrados = consultar(LikePattern::contains('100%'));

    expect($encontrados)->toBe(['100% natural']);
});

/** Y lo mismo con el guion bajo, que en LIKE es «un carácter cualquiera». */
it('trata el guion bajo del usuario como texto', function () {
    $encontrados = consultar(LikePattern::contains('a_b'));

    expect($encontrados)->toBe(['a_b']);
});

/**
 * El propio carácter de escape tiene que poder buscarse.
 *
 * Es el caso que se olvida al cambiar de carácter: si `!` no se escapa a sí
 * mismo, buscar «signo!» produce un patrón donde el `!` se come la letra
 * siguiente y la consulta devuelve otra cosa —o falla—.
 */
it('deja buscar el propio carácter de escape', function () {
    $encontrados = consultar(LikePattern::contains('signo!'));

    expect($encontrados)->toBe(['signo! de admiración']);
});

/**
 * Y la barra invertida, que era el carácter de escape anterior.
 *
 * Con `ESCAPE '\'` esto era justo lo que no se podía buscar sin que el motor se
 * confundiera.
 */
it('deja buscar una barra invertida', function () {
    $encontrados = consultar(LikePattern::contains('con\\barras'));

    expect($encontrados)->toBe(['ruta\\con\\barras']);
});

/**
 * El SQL no puede llevar una barra suelta en la cláusula ESCAPE.
 *
 * Es el único aserto sobre la cadena, y está a propósito: es el cepo del fallo
 * concreto. Lo que importa de verdad —que la consulta se ejecute— lo comprueban
 * los de arriba.
 */
it('no emite una barra invertida como carácter de escape', function () {
    $sql = DB::table('busquedas')->toSql();

    $consulta = \KoreUi\DataTable\Support\LikePattern::class;
    expect($consulta::ESCAPE)->not->toBe('\\');

    // Y la cláusula, tal como sale, no lleva ninguna barra: ni una —que rompe
    // MySQL— ni dos —que rompen SQLite y PostgreSQL—.
    $emitido = "columna LIKE ? ESCAPE '".$consulta::ESCAPE."'";
    expect($emitido)->not->toContain('\\');
});

/** Ejecuta el patrón contra la tabla y devuelve lo que encuentra. */
function consultar(string $pattern): array
{
    $query = (new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'busquedas';
    })->newQuery();

    LikePattern::where($query, 'texto', $pattern);

    return $query->orderBy('id')->pluck('texto')->all();
}
