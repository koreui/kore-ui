<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Actions\RowAction;
use KoreUi\DataTable\Columns\ActionColumn;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\KoreDataTable;
use KoreUi\Feedback\ConfirmDialog;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

/**
 * Una acción de fila con ->confirm() abre el diálogo desde el navegador con el
 * payload de RowAction::buildKoreConfirmPayload(), sin pasar por
 * Confirm::send(). Su método nunca entraba en $koreConfirmable, así que
 * handleConfirmCallback() descartaba el callback en silencio: el diálogo se
 * abría, se pulsaba «Confirmar» y no pasaba nada.
 */
class TablaAccionConfirmada extends KoreDataTable
{
    public array $editados = [];

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return TestUser::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Nombre', 'name'),
            ActionColumn::make()->actions([
                RowAction::make('eliminar', 'Eliminar')->wireMethod('eliminar')->confirm('¿Eliminar?'),
                RowAction::make('editar', 'Editar')->wireMethod('editar'),
                RowAction::make('purgar', 'Purgar')->wireMethod('purgar')->confirm('¿Purgar?'),
            ]),
        ];
    }

    public function eliminar($id): void
    {
        TestUser::whereKey($id)->delete();
    }

    public function editar($id): void
    {
        $this->editados[] = $id;
    }

    protected function purgar($id): void
    {
        TestUser::query()->delete();
    }
}

beforeEach(function () {
    Livewire::component('kore-confirm-dialog', ConfirmDialog::class);

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

it('ejecuta el wireMethod al confirmar una acción de fila', function () {
    $tabla  = Livewire::test(TablaAccionConfirmada::class);
    $accion = RowAction::make('eliminar', 'Eliminar')->wireMethod('eliminar')->confirm('¿Eliminar?');
    $fila   = TestUser::find(2);

    // Mismo payload que pinta action.blade.php en el botón.
    $argumentos = $accion->buildKoreConfirmPayload($fila, $tabla->id(), 'id')['arguments'];

    Livewire::test(ConfirmDialog::class, $argumentos)
        ->call('accept')
        ->assertDispatched('kore:confirm-callback',
            method: 'eliminar',
            params: [2],
            callerRef: $tabla->id(),
        );

    $tabla->dispatch('kore:confirm-callback',
        method: 'eliminar',
        params: [2],
        callerRef: $tabla->id(),
    );

    expect(TestUser::find(2))->toBeNull()
        ->and(TestUser::count())->toBe(2);
});

it('ignora un callback para un método que no es una acción de fila con confirm', function () {
    $tabla = Livewire::test(TablaAccionConfirmada::class);

    // 'editar' es una acción de fila, pero sin confirm: su camino es wire:click.
    $tabla->dispatch('kore:confirm-callback', method: 'editar', params: [1], callerRef: $tabla->id());

    // 'flushDefinitionCache' es público, pero no lo declara ninguna acción.
    $tabla->dispatch('kore:confirm-callback', method: 'flushDefinitionCache', params: [1], callerRef: $tabla->id());

    expect($tabla->get('editados'))->toBe([]);
});

it('ignora un callback para un método protegido aunque lo declare una acción', function () {
    $tabla = Livewire::test(TablaAccionConfirmada::class);

    $tabla->dispatch('kore:confirm-callback', method: 'purgar', params: [1], callerRef: $tabla->id());

    expect(TestUser::count())->toBe(3);
});

it('ignora un callback cuyos parámetros no son una única clave escalar', function (array $params) {
    $tabla = Livewire::test(TablaAccionConfirmada::class);

    $tabla->dispatch('kore:confirm-callback', method: 'eliminar', params: $params, callerRef: $tabla->id());

    expect(TestUser::count())->toBe(3);
})->with([
    'sin parámetros'    => [[]],
    'dos parámetros'    => [[1, 2]],
    'un array'          => [[[1]]],
    'con nombre'        => [['id' => 1]],
]);

it('ignora un callback dirigido a otro componente', function () {
    $tabla = Livewire::test(TablaAccionConfirmada::class);

    $tabla->dispatch('kore:confirm-callback', method: 'eliminar', params: [1], callerRef: 'otro-componente');

    expect(TestUser::count())->toBe(3);
});
