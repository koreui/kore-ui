<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestExportTable;
use KoreUi\Tests\DataTable\Fixtures\TestScopedEditableTable;
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
    });
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// ---------------------------------------------------------------------------
// F.1 — Sorting whitelist + direction normalization
// ---------------------------------------------------------------------------

it('ignores sorts on non-whitelisted (non-sortable) columns', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'city' => 'Madrid'],
        ['name' => 'Bob', 'email' => 'b@t.com', 'city' => 'Barcelona'],
    ]);

    // 'city' is searchable but NOT sortable in TestTable → must be dropped
    // even though the client managed to set it on the public property.
    $component = Livewire::test(TestTable::class)->set('sorts', ['city' => 'asc']);

    expect($component->instance()->getActiveSorts())->toBe([]);
});

it('normalizes a tampered sort direction instead of crashing', function () {
    TestUser::insert([
        ['name' => 'Charlie', 'email' => 'c@t.com'],
        ['name' => 'Alice', 'email' => 'a@t.com'],
        ['name' => 'Bob', 'email' => 'b@t.com'],
    ]);

    // An invalid direction must coerce to 'asc' (no InvalidArgumentException).
    $component = Livewire::test(TestTable::class)->set('sorts', ['name' => 'evil; drop']);
    $rows = $component->instance()->getRows();

    expect(collect($rows->items())->first()->name)->toBe('Alice');
});

it('applies a whitelisted sort case-insensitively (desc)', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com'],
        ['name' => 'Charlie', 'email' => 'c@t.com'],
        ['name' => 'Bob', 'email' => 'b@t.com'],
    ]);

    $component = Livewire::test(TestTable::class)->set('sorts', ['name' => 'DESC']);
    $rows = $component->instance()->getRows();

    expect(collect($rows->items())->first()->name)->toBe('Charlie');
});

// ---------------------------------------------------------------------------
// F.2 — Inline editing respects the query() scope (IDOR)
// ---------------------------------------------------------------------------

it('does not update a record outside the query scope (IDOR)', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => true],
        ['name' => 'Bob', 'email' => 'b@t.com', 'is_active' => false],
    ]);

    // Bob (id 2) is outside the table's scope (active users only).
    Livewire::test(TestScopedEditableTable::class)
        ->call('updateCell', 2, 'name', 'Hacked')
        ->assertDispatched('kore:datatable-edit-error');

    expect(TestUser::find(2)->name)->toBe('Bob');
});

it('updates a record that is within the query scope', function () {
    TestUser::insert([
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => true],
    ]);

    Livewire::test(TestScopedEditableTable::class)
        ->call('updateCell', 1, 'name', 'Alice Updated')
        ->assertDispatched('kore:datatable-edit-success');

    expect(TestUser::find(1)->name)->toBe('Alice Updated');
});

// ---------------------------------------------------------------------------
// F.5 — CSV / formula injection in export
// ---------------------------------------------------------------------------

it('neutralizes spreadsheet formulas in CSV export', function () {
    TestUser::insert([
        ['name' => '=HYPERLINK("http://evil")', 'email' => 'x@t.com', 'is_active' => true],
        ['name' => 'Alice', 'email' => 'a@t.com', 'is_active' => true],
    ]);

    $response = Livewire::test(TestExportTable::class)->instance()->exportAs('csv');

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    // The formula is prefixed with a single quote; plain values are untouched.
    expect($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain('Alice')
        ->and($csv)->not->toContain("'Alice");
});

// ---------------------------------------------------------------------------
// F.7 — LIKE wildcards in search are treated as literals
// ---------------------------------------------------------------------------

it('treats an underscore in search as a literal, not a wildcard', function () {
    TestUser::insert([
        ['name' => 'Adam_Smith', 'email' => 'adam@t.com', 'city' => 'Madrid'],
        ['name' => 'Alice', 'email' => 'alice@t.com', 'city' => 'Madrid'],
    ]);

    Livewire::test(TestTable::class)
        ->set('search', '_')
        ->assertSee('Adam_Smith')
        ->assertDontSee('Alice');
});

it('treats a percent sign in search as a literal, not a wildcard', function () {
    TestUser::insert([
        ['name' => '50% Off', 'email' => 'promo@t.com', 'city' => 'Madrid'],
        ['name' => 'Alice', 'email' => 'alice@t.com', 'city' => 'Madrid'],
    ]);

    Livewire::test(TestTable::class)
        ->set('search', '%')
        ->assertSee('50% Off')
        ->assertDontSee('Alice');
});
