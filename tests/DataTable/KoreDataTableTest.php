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
        ->set('perPage', 2)
        ->assertSet('perPage', 2);
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
