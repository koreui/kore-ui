<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
use KoreUi\Tests\DataTable\Fixtures\TestConfigExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestCountingTable;
use KoreUi\Tests\DataTable\Fixtures\TestExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestResponsiveTable;
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
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 25],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// ---------------------------------------------------------------------------
// P-5 — memoización por request
// ---------------------------------------------------------------------------

it('builds the table definition once per request', function () {
    TestCountingTable::resetCounters();

    Livewire::test(TestCountingTable::class);

    // Antes: columns() trece veces desde el módulo más las de Blade, y filters()
    // cuatro — con un options(Ciudad::pluck(...)) eso son cuatro consultas.
    expect(TestCountingTable::$columnCalls)->toBe(1)
        ->and(TestCountingTable::$filterCalls)->toBe(1)
        ->and(TestCountingTable::$presetCalls)->toBe(1)
        ->and(TestCountingTable::$bulkCalls)->toBe(1);
});

it('rebuilds the definition on the next request', function () {
    TestCountingTable::resetCounters();

    // El caché es una propiedad protected: Livewire no la serializa, así que
    // cada petición vuelve a preguntar. Es lo que queremos.
    Livewire::test(TestCountingTable::class)->call('$refresh');

    expect(TestCountingTable::$columnCalls)->toBe(2);
});

it('evaluates a hiddenIf callback once per column', function () {
    $calls = 0;
    $column = Column::make('Nombre', 'name')->hiddenIf(function () use (&$calls) {
        $calls++;

        return false;
    });

    $column->isHidden();
    $column->isHidden();
    $column->isHidden();

    expect($calls)->toBe(1);
});

// ---------------------------------------------------------------------------
// P-4 — una sola variante por render en cuanto se conoce el ancho
// ---------------------------------------------------------------------------

it('renders both variants until the client reports its width', function () {
    $component = Livewire::test(TestResponsiveTable::class);

    expect($component->instance()->shouldRenderTable())->toBeTrue()
        ->and($component->instance()->shouldRenderMobile())->toBeTrue();
});

it('renders only the cards once the client reports a narrow container', function () {
    $component = Livewire::test(TestResponsiveTable::class)->call('setViewport', true);

    expect($component->instance()->shouldRenderTable())->toBeFalse()
        ->and($component->instance()->shouldRenderMobile())->toBeTrue();

    $component->assertDontSeeHtml('<thead');
});

it('renders only the table once the client reports a wide container', function () {
    $component = Livewire::test(TestResponsiveTable::class)->call('setViewport', false);

    expect($component->instance()->shouldRenderTable())->toBeTrue()
        ->and($component->instance()->shouldRenderMobile())->toBeFalse();
});

it('always renders the table in scroll mode', function () {
    $component = Livewire::test(TestTable::class)->call('setViewport', true);

    expect($component->instance()->shouldRenderTable())->toBeTrue()
        ->and($component->instance()->shouldRenderMobile())->toBeFalse();
});

// ---------------------------------------------------------------------------
// A-4 — exporters registrados, sin caídas silenciosas
// ---------------------------------------------------------------------------

it('throws instead of silently falling back to CSV', function () {
    $table = Livewire::test(TestExportTable::class)->instance();
    $table->setExportFormats(['csv', 'xlsx']);

    // Antes el `default` del match devolvía un CsvExporter: el botón "XLSX"
    // descargaba un CSV con extensión .csv sin decir nada.
    expect(fn () => $table->exportAs('xlsx'))
        ->toThrow(InvalidArgumentException::class);
});

it('accepts a custom exporter', function () {
    $table = Livewire::test(TestExportTable::class)->instance();
    $table->registerExporter('csv2', \KoreUi\DataTable\Exports\CsvExporter::class)
        ->setExportFormats(['csv2']);

    expect($table->exportAs('csv2'))
        ->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
});

it('writes RFC 4180 CSV without PHP backslash escaping', function () {
    TestUser::create(['name' => 'Ruta C:\\', 'email' => 'r@t.com']);

    $response = Livewire::test(TestExportTable::class)->instance()->exportAs('csv');

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    // El escape por defecto de PHP habría metido una barra de más.
    expect($csv)->toContain('Ruta C:\\')
        ->and($csv)->not->toContain('C:\\\\');
});

// ---------------------------------------------------------------------------
// P-3 — trabajar sobre el conjunto sin materializarlo
// ---------------------------------------------------------------------------

it('exposes the matching query without executing it', function () {
    $table = Livewire::test(TestExportTable::class)->instance();

    expect($table->matchingQuery())
        ->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class)
        ->and($table->matchingQuery()->count())->toBe(2);
});

it('walks the matching set in chunks', function () {
    foreach (range(1, 30) as $i) {
        TestUser::create(['name' => "User {$i}", 'email' => "u{$i}@t.com"]);
    }

    $table = Livewire::test(TestExportTable::class)->instance();

    $lotes = 0;
    $filas = 0;
    $table->eachMatching(function ($rows) use (&$lotes, &$filas) {
        $lotes++;
        $filas += $rows->count();
    }, chunkSize: 10);

    expect($filas)->toBe(32)
        ->and($lotes)->toBeGreaterThan(1);
});

// ---------------------------------------------------------------------------
// A-3 — lo que se retiró, se retiró
// ---------------------------------------------------------------------------

it('no longer ships the hiddenWhenEmpty no-op', function () {
    expect(method_exists(\KoreUi\DataTable\Actions\BulkAction::class, 'hiddenWhenEmpty'))->toBeFalse();
});

it('no longer ships the unused collapse helper', function () {
    expect(method_exists(TestTable::class, 'getVisibleColumnsForCollapse'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Accesibilidad
// ---------------------------------------------------------------------------

it('announces the result count to screen readers', function () {
    foreach (range(1, 60) as $i) {
        TestUser::create(['name' => "User {$i}", 'email' => "u{$i}@t.com"]);
    }

    Livewire::test(TestTable::class)
        ->assertSeeHtml('aria-live="polite"');
});

it('ties each filter label to its field', function () {
    $html = Livewire::test(\KoreUi\Tests\DataTable\Fixtures\TestFilterTable::class)->html();

    // Un <label> sin `for` no amplía el área de clic ni se anuncia al enfocar.
    expect($html)->toMatch('/<label for="kore-filter-[^"]+-city"/');
});

it('marks the filter drawer as a modal dialog', function () {
    config()->set('kore-ui.datatable.filter_layout', 'drawer');

    Livewire::test(\KoreUi\Tests\DataTable\Fixtures\TestFilterTable::class)
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('x-kore-trap="filtersOpen"');
});

// ---------------------------------------------------------------------------
// Encontrados al implementar P-4: la configuración no sobrevivía al primer
// request, y la opción global de layout de filtros no se aplicaba nunca
// ---------------------------------------------------------------------------

it('keeps configure() applied across requests', function () {
    // Las propiedades de configuración son protected y Livewire no las
    // serializa: con configure() solo en mount(), la tabla volvía a sus valores
    // por defecto en cuanto el usuario paginaba o filtraba.
    $component = Livewire::test(TestResponsiveTable::class);

    expect($component->instance()->getResponsiveMode())->toBe('card');

    $component->call('$refresh');

    expect($component->instance()->getResponsiveMode())->toBe('card');
});

it('keeps export enabled across requests', function () {
    // El caso más grave del mismo fallo: exportAs() responde 403 si
    // isExportEnabled() es false, así que el export se rompía tras la primera
    // interacción con la tabla.
    $component = Livewire::test(TestExportTable::class)->call('$refresh');

    expect($component->instance()->isExportEnabled())->toBeTrue();
});

it('applies the filter layout from config', function () {
    // $filterLayout es una propiedad pública que vale null, y Livewire vuelca
    // las públicas en el scope de la vista por encima de los datos de render:
    // el valor resuelto no llegaba al Blade.
    config()->set('kore-ui.datatable.filter_layout', 'drawer');

    Livewire::test(\KoreUi\Tests\DataTable\Fixtures\TestFilterTable::class)
        ->assertSet('filterLayout', 'drawer')
        ->assertSeeHtml('role="dialog"');
});

it('lets configure() win over the configured filter layout', function () {
    config()->set('kore-ui.datatable.filter_layout', 'drawer');

    // TestFilterTable no llama a setFilterLayout(), así que gana la config.
    // Con una tabla que sí lo llame, gana la tabla.
    $table = Livewire::test(\KoreUi\Tests\DataTable\Fixtures\TestFilterTable::class)->instance();
    $table->setFilterLayout('inline');

    expect($table->getFilterLayout())->toBe('inline');
});
