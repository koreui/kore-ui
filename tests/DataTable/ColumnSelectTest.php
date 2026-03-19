<?php

use Illuminate\Support\Facades\Schema;
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

    TestUser::insert([
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 25],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('shows column select dropdown when enabled', function () {
    Livewire::test(TestTable::class)
        ->assertSeeHtml('Columnas');
});

it('toggles column visibility', function () {
    $component = Livewire::test(TestTable::class);

    // City column should be visible initially
    $component->assertSee('Madrid');

    // Toggle city column off
    $component->call('toggleColumnVisibility', 'city');

    // City should no longer appear in columns
    $columns = $component->viewData('columns');
    $columnFields = collect($columns)->map(fn ($c) => $c->getField())->all();

    expect($columnFields)->not->toContain('city');
});

it('toggles column back on', function () {
    $component = Livewire::test(TestTable::class);

    $component->call('toggleColumnVisibility', 'city');
    $component->call('toggleColumnVisibility', 'city');

    $columns = $component->viewData('columns');
    $columnFields = collect($columns)->map(fn ($c) => $c->getField())->all();

    expect($columnFields)->toContain('city');
});

it('persists deselected columns in session', function () {
    $component = Livewire::test(TestTable::class);
    $component->call('toggleColumnVisibility', 'email');

    expect(session('kore-datatable-columns:' . TestTable::class))->toContain('email');
});

it('resets column selection', function () {
    $component = Livewire::test(TestTable::class);
    $component->call('toggleColumnVisibility', 'email');
    $component->call('toggleColumnVisibility', 'city');
    $component->call('resetColumnSelect');

    $columns = $component->viewData('columns');
    expect($columns)->toHaveCount(4); // All 4 columns visible

    expect(session()->has('kore-datatable-columns:' . TestTable::class))->toBeFalse();
});

it('provides allColumns for select dropdown', function () {
    $component = Livewire::test(TestTable::class);

    $allColumns = $component->viewData('allColumns');
    expect($allColumns)->toHaveCount(4);
});

it('resolveColumns excludes deselected columns', function () {
    $component = Livewire::test(TestTable::class);
    $component->call('toggleColumnVisibility', 'id');
    $component->call('toggleColumnVisibility', 'email');

    $columns = $component->viewData('columns');
    $columnFields = collect($columns)->map(fn ($c) => $c->getField())->all();

    expect($columnFields)->toBe(['name', 'city']);
});
