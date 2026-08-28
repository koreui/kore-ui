<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

/**
 * Las cuatro formas del pie de la tabla.
 *
 * La variante decide cómo se PINTA el paginador, no qué se calcula: en qué
 * página se está y a dónde se puede ir se resuelve una sola vez, antes de
 * elegir la plantilla. Ojo con no confundirla con `pagination_type`, que es
 * otra cosa: ese decide cómo se consulta la base de datos.
 */
beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
    });

    // 60 filas: con 25 por página salen tres, suficiente para que haya números,
    // elipsis no, y las dos flechas activas en la página del medio.
    $filas = [];
    for ($i = 1; $i <= 60; $i++) {
        $filas[] = ['name' => "Usuario {$i}", 'email' => "u{$i}@test.com", 'city' => 'Madrid'];
    }
    TestUser::insert($filas);
});

afterEach(fn () => Schema::dropIfExists('test_users'));

it('pinta la variante que diga la configuración', function (string $variante, string $marca) {
    config()->set('kore-ui.datatable.paginator', $variante);

    Livewire::test(TestTable::class)->assertSee($marca, false);
})->with([
    // Cada una tiene una marca que no comparte con las demás.
    'default' => ['default', 'bg-kore-primary text-kore-primary-fg'],
    'compact' => ['compact', 'tabular-nums'],
    'minimal' => ['minimal', 'after:bg-kore-primary'],
    'simple'  => ['simple', 'Anterior'],
]);

it('la tabla manda sobre la configuración global', function () {
    config()->set('kore-ui.datatable.paginator', 'default');

    $tabla = new class extends TestTable
    {
        protected string $paginatorVariant = 'compact';
    };

    expect((new $tabla)->getPaginatorVariant())->toBe('compact');
});

it('una variante inventada cae en la de siempre', function () {
    config()->set('kore-ui.datatable.paginator', 'ornamentada');

    // Sin este cierre, `@include` de una vista que no existe tumba la página
    // entera por un valor mal escrito en la configuración.
    Livewire::test(TestTable::class)->assertSee('bg-kore-primary text-kore-primary-fg', false);
});

it('solo `default` y `minimal` gastan en calcular la ventana de páginas', function () {
    // `compact` y `simple` no enseñan números, así que la lista de páginas no se
    // construye siquiera.
    config()->set('kore-ui.datatable.paginator', 'compact');
    $compacto = Livewire::test(TestTable::class)->html();

    config()->set('kore-ui.datatable.paginator', 'default');
    $normal = Livewire::test(TestTable::class)->html();

    expect(substr_count($compacto, 'gotoPage'))->toBe(0)
        ->and(substr_count($normal, 'gotoPage'))->toBeGreaterThan(0);
});

it('el control apagado sigue en el recorrido del tabulador', function () {
    // Un `<button disabled>` desaparece del tabulador: quien navega con teclado
    // ve esfumarse el control en vez de encontrarlo apagado. Por eso es un
    // `<span aria-disabled>`.
    config()->set('kore-ui.datatable.paginator', 'compact');

    $html = Livewire::test(TestTable::class)->html();

    expect($html)->toContain('aria-disabled="true"')
        ->and($html)->not->toContain('<button type="button" disabled');
});

it('las cuatro nombran sus flechas', function (string $variante) {
    config()->set('kore-ui.datatable.paginator', $variante);

    $html = Livewire::test(TestTable::class)->html();

    // `simple` las nombra con su propio texto; las otras tres, con aria-label.
    expect(str_contains($html, 'Siguiente'))->toBeTrue();
})->with(['default', 'simple', 'compact', 'minimal']);

it('compact enseña en qué página se está', function () {
    config()->set('kore-ui.datatable.paginator', 'compact');

    Livewire::test(TestTable::class)
        ->call('gotoPage', 2)
        ->assertSee('/ 3', false);
});
