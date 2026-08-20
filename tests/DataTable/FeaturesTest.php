<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Views\Contracts\SavedViewStore;
use KoreUi\DataTable\Views\SavedView;
use KoreUi\Tests\DataTable\Fixtures\TestFeaturesTable;
use KoreUi\Tests\DataTable\Fixtures\TestTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('age')->nullable();
        $table->unsignedBigInteger('company_id')->nullable();
    });

    TestUser::insert([
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'age' => 25],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// ---------------------------------------------------------------------------
// Segunda línea en la celda
// ---------------------------------------------------------------------------

it('renders a second line under the value', function () {
    Livewire::test(TestFeaturesTable::class)
        ->assertSee('Alice')
        ->assertSee('alice@test.com');
});

it('accepts a closure or a field name as description', function () {
    $conClosure = Column::make('Usuario', 'name')->description(fn ($row) => strtoupper($row->email));
    $conCampo   = Column::make('Usuario', 'name')->description('city');

    $row = TestUser::first();

    expect($conClosure->getDescription($row))->toBe('ALICE@TEST.COM')
        ->and($conCampo->getDescription($row))->toBe('Madrid');
});

it('treats an empty description as absent', function () {
    $column = Column::make('Usuario', 'name')->description(fn () => '');

    expect($column->hasDescription())->toBeTrue()
        ->and($column->getDescription(TestUser::first()))->toBeNull();
});

it('supports placing the description above the value', function () {
    expect(Column::make('X', 'x')->description('y', 'above')->getDescriptionPosition())->toBe('above')
        ->and(Column::make('X', 'x')->description('y')->getDescriptionPosition())->toBe('below')
        // Cualquier otra cosa cae en el valor por defecto, no en SQL raro.
        ->and(Column::make('X', 'x')->description('y', 'diagonal')->getDescriptionPosition())->toBe('below');
});

// ---------------------------------------------------------------------------
// Menú por cabecera de columna
// ---------------------------------------------------------------------------

it('sorts in an explicit direction from the header menu', function () {
    // setSort fija la dirección; sortBy la rota. El menú necesita lo primero.
    $component = Livewire::test(TestFeaturesTable::class)->call('setSort', 'name', 'desc');

    expect($component->instance()->getSortDirection('name'))->toBe('desc');

    $component->call('setSort', 'name', 'desc');

    expect($component->instance()->getSortDirection('name'))->toBe('desc');
});

it('refuses to sort by a column that is not sortable', function () {
    $component = Livewire::test(TestFeaturesTable::class)->call('setSort', 'age', 'asc');

    expect($component->instance()->getSortDirection('age'))->toBeNull();
});

it('pins a column from the header menu and remembers it', function () {
    $component = Livewire::test(TestFeaturesTable::class)->call('toggleColumnPin', 'name', 'left');

    $columns = collect($component->instance()->resolveColumns())
        ->keyBy(fn ($c) => $c->getField());

    expect($columns['name']->isPinned())->toBeTrue()
        ->and($columns['name']->getPinnedSide())->toBe('left')
        ->and(session('kore-datatable-pins:' . TestFeaturesTable::class))->toHaveKey('name');
});

it('unpins when the same side is chosen twice', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->call('toggleColumnPin', 'name', 'left')
        ->call('toggleColumnPin', 'name', 'left');

    $columns = collect($component->instance()->resolveColumns())->keyBy(fn ($c) => $c->getField());

    expect($columns['name']->isPinned())->toBeFalse();
});

it('moves a pinned column to the other side', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->call('toggleColumnPin', 'name', 'left')
        ->call('toggleColumnPin', 'name', 'right');

    $columns = collect($component->instance()->resolveColumns())->keyBy(fn ($c) => $c->getField());

    expect($columns['name']->getPinnedSide())->toBe('right');
});

it('ignores a pin for a column that does not exist', function () {
    $component = Livewire::test(TestFeaturesTable::class)->call('toggleColumnPin', 'inventada', 'left');

    expect($component->instance()->columnPins)->toBe([]);
});

it('lets the user override a pin declared by the table', function () {
    $component = Livewire::test(TestFeaturesTable::class);
    $component->instance()->columnPins = ['name' => ''];

    $columns = collect($component->instance()->resolveColumns())->keyBy(fn ($c) => $c->getField());

    expect($columns['name']->isPinned())->toBeFalse();
});

it('renders the header menu when enabled', function () {
    Livewire::test(TestFeaturesTable::class)
        ->assertSeeHtml('wire:click="toggleColumnPin')
        ->assertSeeHtml('Opciones de columna');
});

it('hides the header menu when disabled by config', function () {
    config()->set('kore-ui.datatable.column_menu', false);

    Livewire::test(TestFeaturesTable::class)
        ->assertDontSeeHtml('wire:click="toggleColumnPin');
});

// ---------------------------------------------------------------------------
// Vistas guardadas
// ---------------------------------------------------------------------------

it('saves the current state as a named view', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('filters', ['city' => 'Madrid'])
        ->set('search', 'ali')
        ->set('savedViewName', 'Solo Madrid')
        ->call('saveCurrentView');

    $views = $component->instance()->getSavedViews();

    expect($views)->toHaveCount(1);

    $view = array_values($views)[0];

    expect($view->name)->toBe('Solo Madrid')
        ->and($view->filters)->toBe(['city' => 'Madrid'])
        ->and($view->search)->toBe('ali')
        // El campo del formulario se vacía tras guardar.
        ->and($component->instance()->savedViewName)->toBe('');
});

it('refuses to save a view without a name', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('savedViewName', '   ')
        ->call('saveCurrentView');

    expect($component->instance()->getSavedViews())->toBe([]);
});

it('restores filters, search and columns from a view', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('filters', ['city' => 'Barcelona'])
        ->set('search', 'bob')
        ->call('toggleColumnVisibility', 'city')
        ->call('toggleColumnPin', 'name', 'left')
        ->set('savedViewName', 'Mi vista')
        ->call('saveCurrentView');

    $id = array_key_first($component->instance()->getSavedViews());

    // Se cambia todo y se vuelve a la vista.
    $component->set('filters', [])->set('search', '')->call('resetColumnSelect')->call('resetColumnPins');
    $component->call('applySavedView', $id);

    $table = $component->instance();

    expect($table->filters)->toBe(['city' => 'Barcelona'])
        ->and($table->search)->toBe('bob')
        ->and($table->deselectedColumns)->toBe(['city'])
        ->and($table->columnPins)->toBe(['name' => 'left'])
        ->and($table->activeSavedView)->toBe($id);
});

it('toggles off when the active view is chosen again', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('filters', ['city' => 'Madrid'])
        ->set('savedViewName', 'Madrid')
        ->call('saveCurrentView');

    $id = array_key_first($component->instance()->getSavedViews());

    // Guardar deja la vista activa, así que un solo clic la suelta.
    $component->call('applySavedView', $id);

    expect($component->instance()->activeSavedView)->toBeNull()
        ->and($component->instance()->filters)->toBe([]);
});

it('deletes a view and forgets it was active', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('savedViewName', 'Temporal')
        ->call('saveCurrentView');

    $id = array_key_first($component->instance()->getSavedViews());

    $component->call('deleteSavedView', $id);

    expect($component->instance()->getSavedViews())->toBe([])
        ->and($component->instance()->activeSavedView)->toBeNull();
});

it('does nothing when saved views are disabled', function () {
    // TestTable no las habilita.
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->isSavedViewsEnabled())->toBeFalse()
        ->and($component->instance()->getSavedViews())->toBe([]);
});

it('keeps two tables of the same class from sharing views', function () {
    $a = Livewire::test(TestFeaturesTable::class, ['tableName' => 'uno'])
        ->set('savedViewName', 'Vista de uno')
        ->call('saveCurrentView');

    $b = Livewire::test(TestFeaturesTable::class, ['tableName' => 'dos']);

    expect($a->instance()->getSavedViews())->toHaveCount(1)
        ->and($b->instance()->getSavedViews())->toBe([]);
});

it('can be pointed at a different store', function () {
    // El contrato es el punto de extensión: quien quiera persistencia real
    // implementa esto contra su propia tabla y lo enlaza en el contenedor.
    $store = new class implements SavedViewStore
    {
        public array $saved = [];

        public function all(string $tableKey): array
        {
            return $this->saved;
        }

        public function find(string $tableKey, string $id): ?SavedView
        {
            return $this->saved[$id] ?? null;
        }

        public function save(string $tableKey, SavedView $view): SavedView
        {
            $this->saved[$view->id] = $view;

            return $view;
        }

        public function delete(string $tableKey, string $id): void
        {
            unset($this->saved[$id]);
        }
    };

    app()->instance(SavedViewStore::class, $store);

    Livewire::test(TestFeaturesTable::class)
        ->set('savedViewName', 'En mi almacén')
        ->call('saveCurrentView');

    expect($store->saved)->toHaveCount(1);
});

it('trims an overlong view name', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('savedViewName', str_repeat('a', 200))
        ->call('saveCurrentView');

    $view = array_values($component->instance()->getSavedViews())[0];

    expect(mb_strlen($view->name))->toBe(60);
});

// ---------------------------------------------------------------------------
// F-10 — la sesión de columnas ya no se comparte entre instancias
// ---------------------------------------------------------------------------

it('keeps two tables of the same class from sharing hidden columns', function () {
    Livewire::test(TestFeaturesTable::class, ['tableName' => 'uno'])
        ->call('toggleColumnVisibility', 'city');

    $otra = Livewire::test(TestFeaturesTable::class, ['tableName' => 'dos']);

    expect($otra->instance()->deselectedColumns)->toBe([]);
});

it('ignores a visibility toggle for an unknown column', function () {
    $component = Livewire::test(TestFeaturesTable::class)
        ->call('toggleColumnVisibility', 'inventada');

    expect($component->instance()->deselectedColumns)->toBe([]);
});

it('restores an edited view instead of toggling it off', function () {
    // Guardar deja la vista activa. Si al editar los filtros a mano no se
    // soltara, volver a pulsarla la interpretaría como "salir de la vista".
    $component = Livewire::test(TestFeaturesTable::class)
        ->set('filters', ['city' => 'Madrid'])
        ->set('savedViewName', 'Madrid')
        ->call('saveCurrentView');

    $id = array_key_first($component->instance()->getSavedViews());

    $component->set('filters', ['city' => 'Barcelona']);

    expect($component->instance()->activeSavedView)->toBeNull();

    $component->call('applySavedView', $id);

    expect($component->instance()->filters)->toBe(['city' => 'Madrid'])
        ->and($component->instance()->activeSavedView)->toBe($id);
});

// ---------------------------------------------------------------------------
// F-11 — default() se comporta igual sea cual sea el tipo de fila
// ---------------------------------------------------------------------------

it('falls back to the default for a null attribute', function () {
    TestUser::create(['name' => 'Sin ciudad', 'email' => 's@t.com', 'city' => null]);

    $row = TestUser::where('name', 'Sin ciudad')->first();

    expect(Column::make('Ciudad', 'city')->default('—')->getValue($row))->toBe('—');
});

it('falls back to the default when the row is an array', function () {
    // data_get() resuelve objetos con isset(), así que para un modelo con el
    // atributo a null ya devolvía el default. Con un array la clave existe y el
    // null llegaba tal cual: el comportamiento de default() dependía del tipo
    // de fila.
    expect(Column::make('Ciudad', 'city')->default('—')->getValue(['city' => null]))->toBe('—');
});

it('does not turn a text default into a zero', function () {
    // El caso que de verdad estaba roto: data_get() entrega el marcador '—' a
    // NumberColumn, que lo casteaba a float y mostraba un 0 con pinta de dato
    // real — peor que la celda vacía que se quería evitar.
    TestUser::create(['name' => 'Sin edad', 'email' => 'e@t.com', 'age' => null]);

    $row = TestUser::where('name', 'Sin edad')->first();

    expect(\KoreUi\DataTable\Columns\NumberColumn::make('Edad', 'age')->default('—')->getValue($row))->toBe('—');
});

it('still formats a real number', function () {
    $row = TestUser::where('name', 'Alice')->first();

    expect(\KoreUi\DataTable\Columns\NumberColumn::make('Edad', 'age')->getValue($row))->toBe('30');
});

it('leaves the format callback in charge of nulls', function () {
    // El callback recibe lo que resuelva data_get(), sin que el default se
    // interponga: es el único que puede querer decidir por su cuenta.
    $column = Column::make('Ciudad', 'city')
        ->format(fn ($value) => $value === null ? 'vacío' : $value);

    expect($column->getValue(['city' => null]))->toBe('vacío');
});

it('still returns the value when there is one', function () {
    $row = TestUser::where('name', 'Alice')->first();

    expect(Column::make('Ciudad', 'city')->default('—')->getValue($row))->toBe('Madrid');
});
