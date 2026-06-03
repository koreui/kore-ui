<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestAggregationTable;
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
        $table->decimal('salary', 10, 2)->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// ---------------------------------------------------------------------------
// F.11 — AVG over an empty dataset stays null ("—"), never coerced to 0
// ---------------------------------------------------------------------------

it('returns an empty avg aggregation for an empty dataset', function () {
    $instance = Livewire::test(TestAggregationTable::class)->instance();

    $aggregations = $instance->getAggregations($instance->resolveColumns());

    expect($aggregations['salary']['value'])->toBe('');
});

// ---------------------------------------------------------------------------
// F.12 — perPage coercion is safe when no options are configured
// ---------------------------------------------------------------------------

it('coerces perPage to 25 when no per-page options are configured', function () {
    config()->set('kore-ui.datatable.per_page_options', []);

    Livewire::test(TestTable::class)
        ->set('perPage', 999)
        ->assertSet('perPage', 25);
});

// ---------------------------------------------------------------------------
// D.2 — accessible sortable headers
// ---------------------------------------------------------------------------

it('renders sortable headers with scope and aria-sort', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com'],
    ]);

    Livewire::test(TestTable::class)
        ->assertSeeHtml('scope="col"')
        ->assertSeeHtml('aria-sort="none"');
});

it('reflects the current sort direction in aria-sort', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com'],
    ]);

    Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->assertSeeHtml('aria-sort="ascending"');
});
