<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
use KoreUi\Tests\DataTable\Fixtures\TestBulkTable;
use KoreUi\Tests\DataTable\Fixtures\TestExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestPresetsTable;
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
    });
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// ---------------------------------------------------------------------------
// F.13 — exportMaxRows is enforced even though chunk() overrides ->limit()
// ---------------------------------------------------------------------------

it('caps exported rows at exportMaxRows', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => true],
        ['name' => 'Bob', 'email' => 'b@t.com', 'is_active' => true],
        ['name' => 'Charlie', 'email' => 'c@t.com', 'is_active' => true],
        ['name' => 'Dave', 'email' => 'd@t.com', 'is_active' => true],
        ['name' => 'Eve', 'email' => 'e@t.com', 'is_active' => true],
    ]);

    $component = Livewire::test(TestExportTable::class);
    $component->instance()->setExportMaxRows(2);

    $response = $component->instance()->exportAs('csv');
    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    // Ordered by primary key, only the first two rows are exported.
    expect($csv)->toContain('Alice')
        ->and($csv)->toContain('Bob')
        ->and($csv)->not->toContain('Charlie')
        ->and($csv)->not->toContain('Eve');
});

// ---------------------------------------------------------------------------
// A.3 — preset counts are cached and only recomputed when invalidated
// ---------------------------------------------------------------------------

it('caches preset counts and recomputes only after invalidation', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => true],
        ['name' => 'Bob', 'email' => 'b@t.com', 'is_active' => true],
    ]);

    $instance = Livewire::test(TestPresetsTable::class)->instance();

    expect($instance->getPresetCounts()['active'])->toBe(2)
        ->and($instance->presetCountsLoaded)->toBeTrue();

    // Underlying data changes, but the cached value is reused.
    TestUser::where('name', 'Bob')->delete();
    expect($instance->getPresetCounts()['active'])->toBe(2);

    // After invalidation the count is recomputed from the database.
    $instance->invalidatePresetCounts();
    expect($instance->getPresetCounts()['active'])->toBe(1);
});

// ---------------------------------------------------------------------------
// E.2 — "select all matching" operates on the filtered query, not page ids
// ---------------------------------------------------------------------------

it('runs a bulk action against every matching row', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => false],
        ['name' => 'Bob', 'email' => 'b@t.com', 'is_active' => false],
        ['name' => 'Charlie', 'email' => 'c@t.com', 'is_active' => false],
    ]);

    $component = Livewire::test(TestBulkTable::class)
        ->call('executeBulkActionMatching', 'activate');

    expect(TestUser::where('is_active', true)->count())->toBe(3)
        ->and($component->instance()->activatedIds)->toHaveCount(3);
});

it('select all matching respects the active search/filters', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => false],
        ['name' => 'Bob', 'email' => 'b@t.com', 'is_active' => false],
        ['name' => 'Charlie', 'email' => 'c@t.com', 'is_active' => false],
    ]);

    Livewire::test(TestBulkTable::class)
        ->set('search', 'Alice')
        ->call('executeBulkActionMatching', 'activate');

    expect(TestUser::where('is_active', true)->pluck('name')->all())->toBe(['Alice']);
});

// ---------------------------------------------------------------------------
// A.1 — maxWidth fluent API
// ---------------------------------------------------------------------------

it('exposes a maxWidth fluent setter', function () {
    $column = Column::make('Bio', 'bio')->maxWidth(200);

    expect($column->getMaxWidth())->toBe(200)
        ->and($column->toArray()['maxWidth'])->toBe(200);
});
