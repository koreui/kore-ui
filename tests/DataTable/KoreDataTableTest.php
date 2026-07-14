<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestSearchTable;
use KoreUi\Tests\DataTable\Fixtures\TestTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
    });

    TestUser::insert([
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid'],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona'],
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'city' => 'Madrid'],
        ['name' => 'Diana', 'email' => 'diana@test.com', 'city' => 'Sevilla'],
        ['name' => 'Eve', 'email' => 'eve@test.com', 'city' => 'Barcelona'],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('renders the datatable', function () {
    Livewire::test(TestTable::class)
        ->assertSee('Alice')
        ->assertSee('Bob')
        ->assertSee('alice@test.com')
        ->assertSee('Nombre')
        ->assertSee('Email')
        ->assertSee('Ciudad');
});

it('initializes with config defaults', function () {
    Livewire::test(TestTable::class)
        ->assertSet('perPage', 25)
        ->assertSet('search', '')
        ->assertSet('sorts', []);
});

it('sorts by column ascending', function () {
    Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->assertSet('sorts', ['name' => 'asc']);
});

it('cycles sort direction: asc -> desc -> null', function () {
    Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->assertSet('sorts.name', 'asc')
        ->call('sortBy', 'name')
        ->assertSet('sorts.name', 'desc')
        ->call('sortBy', 'name')
        ->assertSet('sorts', []);
});

it('ignores sort on non-sortable column', function () {
    Livewire::test(TestTable::class)
        ->call('sortBy', 'city')
        ->assertSet('sorts', []);
});

it('supports multi-column sorting', function () {
    Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->call('sortBy', 'email')
        ->assertSet('sorts', ['name' => 'asc', 'email' => 'asc']);
});

it('searches across searchable columns', function () {
    Livewire::test(TestTable::class)
        ->set('search', 'alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob')
        ->assertDontSee('Charlie');
});

it('searches by email', function () {
    Livewire::test(TestTable::class)
        ->set('search', 'bob@test.com')
        ->assertSee('Bob')
        ->assertDontSee('Alice');
});

it('searches by city', function () {
    Livewire::test(TestTable::class)
        ->set('search', 'Madrid')
        ->assertSee('Alice')
        ->assertSee('Charlie')
        ->assertDontSee('Bob');
});

it('shows empty state when search has no results', function () {
    Livewire::test(TestTable::class)
        ->set('search', 'nonexistent_value_xyz')
        ->assertSee('No se encontraron resultados');
});

it('changes per page', function () {
    Livewire::test(TestTable::class)
        ->set('perPage', 50)
        ->assertSet('perPage', 50);
});

it('clamps an out-of-whitelist per page to the first option', function () {
    // perPage is a public (client-hydratable) property; an out-of-range value
    // is coerced to a safe option to avoid paginate(99999) memory blow-ups.
    Livewire::test(TestTable::class)
        ->set('perPage', 99999)
        ->assertSet('perPage', 10);
});

it('renders sort indicators for sortable columns', function () {
    Livewire::test(TestTable::class)
        ->assertSeeHtml('wire:click="sortBy(\'name\')"')
        ->assertSeeHtml('wire:click="sortBy(\'email\')"')
        ->assertSeeHtml('wire:click="sortBy(\'id\')');
});

it('renders search input', function () {
    Livewire::test(TestTable::class)
        ->assertSeeHtml('wire:model.live.debounce');
});

it('renders per page select', function () {
    Livewire::test(TestTable::class)
        ->assertSeeHtml('wire:model.live="perPage"');
});

it('loading overlay uses the .flex modifier so the spinner stays centered', function () {
    // Regression: without `.flex`, Livewire toggles the overlay to display:inline-block
    // (its default), overriding the `flex` class and left-aligning the spinner.
    Livewire::test(TestTable::class)
        ->assertSeeHtml('wire:loading.delay.flex');
});

it('ships the loading overlay hidden, because Livewire has no selector for delay+display', function () {
    // Regresión (1.4.0): Livewire oculta estos overlays en el primer render con un <style>
    // que lista los selectores de atributo UNO A UNO — [wire:loading], [wire:loading.delay],
    // [wire:loading.flex], [wire:loading.delay.short]... — y NO existe ninguno para la
    // combinación delay + display. `[wire:loading.delay.flex]` no encaja con nada, así que
    // sin este style inline el overlay nace VISIBLE y se queda pegado sobre la tabla para
    // siempre: el JS solo lo apaga al TERMINAR una petición, y en el primer render no hay
    // ninguna. El `.flex` y el `style` van juntos o no van.
    Livewire::test(TestTable::class)
        ->assertSeeHtml('wire:loading.delay.flex style="display: none"');
});

it('renders the loading overlay outside the scroll container', function () {
    // Regression: an `absolute inset-0` INSIDE the overflow-x-auto wrapper scrolls along
    // with the content, leaving the right-hand columns uncovered once the user scrolls.
    // The overlay must be anchored to a non-scrolling parent, i.e. come before the wrapper.
    $html = Livewire::test(TestTable::class)->html();

    $overlay = strpos($html, 'wire:loading.delay.flex');
    $scroller = strpos($html, 'data-table-wrapper');

    expect($overlay)->not->toBeFalse()
        ->and($scroller)->not->toBeFalse()
        ->and($overlay)->toBeLessThan($scroller);
});

it('searches in relation fields with dot notation', function () {
    // Columns with invalid relation/field parts are silently skipped before orWhereHas is called.
    // The valid 'name' column still produces correct results.
    expect(fn () => Livewire::test(TestSearchTable::class)
        ->set('search', 'Alice')
        ->assertSee('Alice')
    )->not->toThrow(Exception::class);
});

it('skips columns with invalid relation field names', function () {
    // Invalid simple fields and invalid dot-notation parts are silently skipped.
    // Only the valid 'name' column is searched, so 'Bob' must not appear when searching 'Alice'.
    Livewire::test(TestSearchTable::class)
        ->set('search', 'Alice')
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});
