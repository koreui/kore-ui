<?php

use KoreUi\DataTable\Actions\RowAction;
use KoreUi\DataTable\Columns\ActionColumn;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;
use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

/**
 * `@js()` dentro del atributo de un componente Blade.
 *
 * La directiva no se compila en el scope del padre: llega literal al hijo, y
 * como el hijo se compila desde su propio archivo, nadie la procesa nunca. El
 * atributo acababa en el DOM tal cual —`marcar(@js(data_get($row, ...)))`— así
 * que todas las filas mandaban la misma cadena literal en vez de su clave.
 *
 * La 1.7.x lo arregló en las celdas copiables y en el desplegable del modo
 * `collapse`, pero se quedó sin arreglar en el desplegable de acciones por fila:
 * el mismo patrón, dos líneas más abajo del que sí se corrigió.
 */
class TablaAccionesDesplegable extends KoreDataTable
{
    public function configure(): void
    {
        $this->perPage = 3;
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name'),
            ActionColumn::make()->dropdown()->actions([
                RowAction::make('editar', 'Editar')->icon('pencil')->wireMethod('marcar'),
            ]),
        ];
    }

    public function marcar($id): void {}
}

beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('city')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('age')->nullable();
    });

    TestUser::insert(collect(range(1, 3))->map(fn ($i) => [
        'name'  => "Usuario {$i}",
        'email' => "u{$i}@test.com",
    ])->all());
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('no deja la directiva sin compilar en el HTML', function () {
    $html = Livewire::test(TablaAccionesDesplegable::class)->html();

    expect($html)->not->toContain('@js(')
        ->and($html)->not->toContain('data_get($row');
});

it('manda la clave de cada fila, y una distinta por fila', function () {
    $filas = TestUser::query()->orderBy('id')->take(3)->pluck('id');

    $html = Livewire::test(TablaAccionesDesplegable::class)->html();

    preg_match_all('/wire:click="marcar\(([^)]*)\)"/', $html, $m);

    expect($m[1])->not->toBeEmpty();

    $enviados = array_map(fn ($v) => trim($v, '"\''), $m[1]);
    foreach ($filas as $id) {
        expect($enviados)->toContain((string) $id);
    }

    // Y no todas la misma: era el síntoma del fallo.
    expect(array_unique($enviados))->toHaveCount(count($enviados));
});
