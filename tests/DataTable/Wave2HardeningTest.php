<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Filters\BooleanFilter;
use KoreUi\DataTable\Filters\DateFilter;
use KoreUi\DataTable\Filters\DateRangeFilter;
use KoreUi\DataTable\Filters\MultiSelectFilter;
use KoreUi\DataTable\Filters\NumberFilter;
use KoreUi\DataTable\Filters\NumberRangeFilter;
use KoreUi\DataTable\Filters\SelectFilter;
use KoreUi\DataTable\Filters\TextFilter;
use KoreUi\Tests\DataTable\Fixtures\TestAuthorizedBulkTable;
use KoreUi\Tests\DataTable\Fixtures\TestCompany;
use KoreUi\Tests\DataTable\Fixtures\TestFilterTable;
use KoreUi\Tests\DataTable\Fixtures\TestRelationFilterTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('test_companies', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('city')->nullable();
    });

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
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 25],
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 45],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_companies');
});

// ---------------------------------------------------------------------------
// S-1 — un valor con la forma equivocada no llega a la consulta
// ---------------------------------------------------------------------------

it('survives an array where a select filter expects a scalar', function () {
    // Antes: binding de array → PDOException → 500, con una URL compartible
    // (?filter[city][]=Madrid) capaz de romper la página de cualquiera.
    Livewire::test(TestFilterTable::class)
        ->set('filters', ['city' => ['Madrid', 'Barcelona']])
        ->assertOk()
        ->assertSee('Alice')
        ->assertSee('Bob');
});

it('survives an array where a number filter expects a scalar', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters', ['age' => ['30']])
        ->assertOk()
        ->assertSee('Alice');
});

it('survives non-numeric text in a number filter', function () {
    // En PostgreSQL esto es un error de SQL, no una comparación vacía.
    expect(NumberFilter::make('Edad', 'age')->sanitize('no soy un número'))->toBeNull();

    Livewire::test(TestFilterTable::class)
        ->set('filters', ['age' => 'abc'])
        ->assertOk()
        ->assertSee('Alice');
});

it('drops a text filter value that is not a scalar', function () {
    expect(TextFilter::make('Nombre', 'name')->sanitize(['a', 'b']))->toBeNull()
        ->and(TextFilter::make('Nombre', 'name')->sanitize('Ali'))->toBe('Ali');
});

it('coerces booleans instead of trusting a cast', function () {
    $filter = BooleanFilter::make('Activo', 'is_active');

    expect($filter->sanitize('1'))->toBeTrue()
        ->and($filter->sanitize('0'))->toBeFalse()
        // (bool) 'false' era true; filter_var lo entiende.
        ->and($filter->sanitize('false'))->toBeFalse()
        ->and($filter->sanitize(''))->toBeNull()
        ->and($filter->sanitize(['x']))->toBeNull();
});

it('drops range values that are not numeric', function () {
    $filter = NumberRangeFilter::make('Edad', 'age');

    expect($filter->sanitize(['min' => '10', 'max' => 'basura']))
        ->toBe(['min' => 10, 'max' => null])
        ->and($filter->sanitize('no soy un array'))->toBeNull();
});

it('normalizes dates and drops the unparseable ones', function () {
    $filter = DateFilter::make('Creado', 'created_at');

    expect($filter->sanitize('2026-03-15'))->toBe('2026-03-15')
        ->and($filter->sanitize('no soy una fecha'))->toBeNull()
        ->and($filter->sanitize(['2026-03-15']))->toBeNull();

    $range = DateRangeFilter::make('Creado', 'created_at');

    expect($range->sanitize(['start' => '2026-01-01', 'end' => 'basura']))
        ->toBe(['start' => '2026-01-01', 'end' => null]);
});

it('rejects a select value outside the declared options', function () {
    $filter = SelectFilter::make('Ciudad', 'city')->options([
        ['label' => 'Madrid', 'value' => 'Madrid'],
        ['label' => 'Barcelona', 'value' => 'Barcelona'],
    ]);

    expect($filter->sanitize('Madrid'))->toBe('Madrid')
        ->and($filter->sanitize('Sevilla'))->toBeNull();
});

it('accepts any scalar when the select has no declared options', function () {
    // Opciones dinámicas o filtro con callback: no hay lista contra la que
    // contrastar y la validación recae en la consulta.
    $filter = SelectFilter::make('Ciudad', 'city');

    expect($filter->sanitize('Sevilla'))->toBe('Sevilla');
});

it('filters a multi-select against its options and its max', function () {
    $filter = MultiSelectFilter::make('Ciudad', 'city')
        ->options(['Madrid' => 'Madrid', 'Barcelona' => 'Barcelona'])
        ->max(1);

    expect($filter->sanitize(['Madrid', 'Sevilla', 'Barcelona']))->toBe(['Madrid'])
        ->and($filter->sanitize('no soy un array'))->toBeNull();
});

it('keeps pills and query in agreement about what is filtered', function () {
    // Un valor rechazado no debe contarse ni pintarse: la interfaz anunciaría
    // un filtro que la consulta no aplica.
    $component = Livewire::test(TestFilterTable::class)
        ->set('filters', ['city' => ['Madrid']]);

    expect($component->instance()->getActiveFilters())->toBe([])
        ->and($component->instance()->getActiveFilterCount())->toBe(0);
});

// ---------------------------------------------------------------------------
// S-2 — comodines LIKE también en los filtros, no solo en la búsqueda
// ---------------------------------------------------------------------------

it('treats a percent sign in a text filter as a literal', function () {
    TestUser::insert([['name' => '50% Off', 'email' => 'promo@t.com', 'city' => 'Madrid']]);

    Livewire::test(TestFilterTable::class)
        ->set('filters', ['name' => '%'])
        ->assertSee('50% Off')
        ->assertDontSee('Alice');
});

it('treats an underscore in a text filter as a literal', function () {
    TestUser::insert([['name' => 'Adam_Smith', 'email' => 'adam@t.com', 'city' => 'Madrid']]);

    Livewire::test(TestFilterTable::class)
        ->set('filters', ['name' => '_'])
        ->assertSee('Adam_Smith')
        ->assertDontSee('Alice');
});

// ---------------------------------------------------------------------------
// S-6 — filtros sobre relaciones
// ---------------------------------------------------------------------------

it('filters through a relation with dot notation', function () {
    $acme = TestCompany::create(['name' => 'Acme', 'city' => 'Madrid']);
    $globex = TestCompany::create(['name' => 'Globex', 'city' => 'Bilbao']);

    TestUser::create(['name' => 'Ana', 'email' => 'ana@t.com', 'company_id' => $acme->id]);
    TestUser::create(['name' => 'Beto', 'email' => 'beto@t.com', 'company_id' => $globex->id]);

    // Antes: where('company.name', 'like', …) → SQL inválido → 500.
    Livewire::test(TestRelationFilterTable::class)
        ->set('filters', ['company_name' => 'Acme'])
        ->assertOk()
        ->assertSee('Ana')
        ->assertDontSee('Beto');
});

it('filters through a relation with a select filter', function () {
    $acme = TestCompany::create(['name' => 'Acme', 'city' => 'Madrid']);
    $globex = TestCompany::create(['name' => 'Globex', 'city' => 'Bilbao']);

    TestUser::create(['name' => 'Ana', 'email' => 'ana@t.com', 'company_id' => $acme->id]);
    TestUser::create(['name' => 'Beto', 'email' => 'beto@t.com', 'company_id' => $globex->id]);

    Livewire::test(TestRelationFilterTable::class)
        ->set('filters', ['company_city' => 'Bilbao'])
        ->assertOk()
        ->assertSee('Beto')
        ->assertDontSee('Ana');
});

it('leaves a table-qualified column alone when it is not a relation', function () {
    // `test_users.city` no es una relación del modelo: se deja pasar tal cual,
    // que es como se comportaba antes de soportar dot-notation.
    $filter = SelectFilter::make('Ciudad', 'test_users.city');
    $query  = TestUser::query();

    $filter->apply($query, 'Madrid');

    expect($query->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// S-3 — hidden() no es autorización; authorize() sí
// ---------------------------------------------------------------------------

it('refuses to run a bulk action whose authorize() says no', function () {
    TestAuthorizedBulkTable::$ranForbidden = false;

    Livewire::test(TestAuthorizedBulkTable::class)
        ->call('executeBulkAction', 'forbidden', ['1'])
        ->assertStatus(403);

    expect(TestAuthorizedBulkTable::$ranForbidden)->toBeFalse();
});

it('refuses to run a hidden bulk action', function () {
    TestAuthorizedBulkTable::$ranHidden = false;

    // Esconder el botón nunca impidió llamar al método desde la consola.
    Livewire::test(TestAuthorizedBulkTable::class)
        ->call('executeBulkAction', 'secret', ['1']);

    expect(TestAuthorizedBulkTable::$ranHidden)->toBeFalse();
});

it('still runs an authorized bulk action', function () {
    Livewire::test(TestAuthorizedBulkTable::class)
        ->call('executeBulkAction', 'allowed', ['1'])
        ->assertOk();
});

it('refuses to apply a hidden preset', function () {
    $table = Livewire::test(\KoreUi\Tests\DataTable\Fixtures\TestPresetsTable::class);

    $table->call('applyPreset', 'hidden_preset');

    expect($table->instance()->activePreset)->toBeNull();
});

// ---------------------------------------------------------------------------
// A-5 — los IDs del cliente se recortan al conjunto autorizado
// ---------------------------------------------------------------------------

it('drops ids outside the table scope before running a bulk action', function () {
    // Bob (id 2) está inactivo: fuera del query() de esta tabla.
    $component = Livewire::test(TestAuthorizedBulkTable::class)
        ->call('executeBulkAction', 'allowed', ['1', '2', '3', '9999']);

    expect($component->instance()->touched)->toBe(['1', '3']);
});

it('keeps a range selection within the visible page', function () {
    Livewire::test(\KoreUi\Tests\DataTable\Fixtures\TestBulkTable::class)
        ->call('selectRange', ['1', '2', '424242'])
        ->assertSet('selected', ['1', '2']);
});

it('locks the properties the client should not write', function () {
    $component = Livewire::test(TestAuthorizedBulkTable::class);

    expect(fn () => $component->set('pendingBulkIdentifier', 'forbidden'))
        ->toThrow(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
});

it('locks deferred loading state', function () {
    $component = Livewire::test(TestAuthorizedBulkTable::class);

    expect(fn () => $component->set('dataLoaded', false))
        ->toThrow(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
});
