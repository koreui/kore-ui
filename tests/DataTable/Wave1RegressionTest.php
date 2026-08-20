<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\NumberColumn;
use KoreUi\Tests\DataTable\Fixtures\TestCompany;
use KoreUi\Tests\DataTable\Fixtures\TestConfigExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestFilterTable;
use KoreUi\Tests\DataTable\Fixtures\TestRelationExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('test_companies', function ($table) {
        $table->id();
        $table->string('name');
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
});

afterEach(function () {
    Schema::dropIfExists('test_users');
    Schema::dropIfExists('test_companies');
});

// ---------------------------------------------------------------------------
// F-1 — la configuración de export dejó de ser letra muerta
// ---------------------------------------------------------------------------

it('enables export from config without touching configure()', function () {
    config()->set('kore-ui.datatable.export.enabled', true);
    config()->set('kore-ui.datatable.export.formats', ['csv']);
    config()->set('kore-ui.datatable.export.max_rows', 250);

    $table = Livewire::test(TestConfigExportTable::class)->instance();

    expect($table->isExportEnabled())->toBeTrue()
        ->and($table->getExportFormats())->toBe(['csv']);
});

it('keeps export disabled when config says so', function () {
    config()->set('kore-ui.datatable.export.enabled', false);

    expect(Livewire::test(TestConfigExportTable::class)->instance()->isExportEnabled())->toBeFalse();
});

it('lets configure() win over the global export config', function () {
    // La config apaga el export; la tabla lo enciende en configure(), que corre
    // después. Es el orden que hace utilizable un default global.
    config()->set('kore-ui.datatable.export.enabled', false);

    expect(Livewire::test(TestExportTable::class)->instance()->isExportEnabled())->toBeTrue();
});

// ---------------------------------------------------------------------------
// P-1 — el export hace eager loading igual que la pantalla
// ---------------------------------------------------------------------------

it('eager loads relations when exporting instead of querying per row', function () {
    $company = TestCompany::create(['name' => 'Acme']);

    foreach (range(1, 20) as $i) {
        TestUser::create([
            'name' => "User {$i}", 'email' => "u{$i}@t.com", 'company_id' => $company->id,
        ]);
    }

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $response = Livewire::test(TestRelationExportTable::class)->instance()->exportAs('csv');

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    // Con eager loading son un puñado de consultas (chunk + relación). Sin él,
    // una por cada una de las 20 filas exportadas.
    expect($queries)->toBeLessThan(10)
        ->and(substr_count($csv, 'Acme'))->toBe(20);
});

// ---------------------------------------------------------------------------
// F-7 — ordenar vuelve a la primera página
// ---------------------------------------------------------------------------

it('resets the page when sorting', function () {
    foreach (range(1, 80) as $i) {
        TestUser::create(['name' => "User {$i}", 'email' => "u{$i}@t.com"]);
    }

    $component = Livewire::test(TestTable::class)->call('gotoPage', 3);
    expect($component->instance()->getPage())->toBe(3);

    $component->call('sortBy', 'name');

    expect($component->instance()->getPage())->toBe(1);
});

it('resets the page when removing or clearing sorts', function () {
    foreach (range(1, 80) as $i) {
        TestUser::create(['name' => "User {$i}", 'email' => "u{$i}@t.com"]);
    }

    $component = Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->call('gotoPage', 3);

    $component->call('removeSortBy', 'name');
    expect($component->instance()->getPage())->toBe(1);

    $component->call('sortBy', 'name')->call('gotoPage', 2)->call('clearSorts');
    expect($component->instance()->getPage())->toBe(1);
});

it('keeps "select all matching" alive across a sort', function () {
    // Ordenar no cambia el conjunto de filas, solo su orden: la selección
    // "todo lo que coincide" sigue siendo válida y no debe soltarse.
    TestUser::create(['name' => 'Alice', 'email' => 'a@t.com']);

    Livewire::test(TestTable::class)
        ->call('enableSelectAllMatching')
        ->assertSet('selectAllMatching', true)
        ->call('sortBy', 'name')
        ->assertSet('selectAllMatching', true);
});

// ---------------------------------------------------------------------------
// D-2 — el conteo de filtros viaja como propiedad, no como llamada a método
// ---------------------------------------------------------------------------

it('publishes the active filter count as a component property', function () {
    TestUser::create(['name' => 'Alice', 'email' => 'a@t.com', 'city' => 'Madrid']);

    Livewire::test(TestFilterTable::class)
        ->assertSet('filterCount', 0)
        ->set('filters', ['city' => 'Madrid'])
        ->assertSet('filterCount', 1)
        ->set('filters', ['city' => 'Madrid', 'name' => 'Ali'])
        ->assertSet('filterCount', 2)
        ->call('resetAllFilters')
        ->assertSet('filterCount', 0);
});

it('renders the filter badge from $wire, never from a method call', function () {
    TestUser::create(['name' => 'Alice', 'email' => 'a@t.com', 'city' => 'Madrid']);

    // $wire.getActiveFilterCount() devuelve una Promise: en x-if nunca se cumple
    // y además dispara un round-trip por evaluación.
    Livewire::test(TestFilterTable::class)
        ->assertDontSeeHtml('$wire.getActiveFilterCount()')
        ->assertSeeHtml('$wire.filterCount');
});

// ---------------------------------------------------------------------------
// F-2 / X-6 — toolbar
// ---------------------------------------------------------------------------

it('uses the configured search debounce', function () {
    config()->set('kore-ui.datatable.search_debounce', 750);

    Livewire::test(TestTable::class)
        ->assertSeeHtml('wire:model.live.debounce.750ms="search"');
});

it('labels the per-page select for screen readers', function () {
    Livewire::test(TestTable::class)
        ->assertSeeHtml('aria-label="Por página"');
});

// ---------------------------------------------------------------------------
// P-2 — ventana de paginación
// ---------------------------------------------------------------------------

it('renders a bounded page window instead of every page', function () {
    foreach (range(1, 500) as $i) {
        TestUser::create(['name' => "User {$i}", 'email' => "u{$i}@t.com"]);
    }

    // 500 filas a 25 por página = 20 páginas; en la 10 la ventana es
    // 1 … 8 9 10 11 12 … 20 y nada más.
    $html = Livewire::test(TestTable::class)->call('gotoPage', 10)->html();

    preg_match_all('/wire:click="gotoPage\((\d+)\)"/', $html, $matches);

    expect(array_map('intval', $matches[1]))->toBe([1, 8, 9, 11, 12, 20]);
});

it('does not emit ellipsis when every page fits in the window', function () {
    foreach (range(1, 60) as $i) {
        TestUser::create(['name' => "User {$i}", 'email' => "u{$i}@t.com"]);
    }

    // 60 filas = 3 páginas: caben enteras, sin elipsis.
    $html = Livewire::test(TestTable::class)->html();

    preg_match_all('/wire:click="gotoPage\((\d+)\)"/', $html, $matches);

    expect(array_map('intval', $matches[1]))->toBe([2, 3])
        ->and($html)->not->toContain('…');
});

// ---------------------------------------------------------------------------
// A-2 — NumberColumn con un único punto de formato
// ---------------------------------------------------------------------------

it('formats numbers identically for cells and aggregations', function () {
    $column = NumberColumn::make('Precio', 'price')->decimals(2)->prefix('$');

    expect($column->getValue((object) ['price' => 1234.5]))->toBe('$1,234.50')
        ->and($column->formatAggregationValue(1234.5))->toBe('$1,234.50');
});

it('formats money through intl when the extension is available', function () {
    expect(extension_loaded('intl'))->toBeTrue();

    $column = NumberColumn::make('Precio', 'price')->money('EUR', 'es_ES');

    expect($column->getValue((object) ['price' => 1234.5]))->toContain('1.234,50');
});
