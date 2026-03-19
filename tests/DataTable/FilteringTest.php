<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestFilterTable;
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
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 45],
        ['name' => 'Diana', 'email' => 'diana@test.com', 'city' => 'Sevilla', 'is_active' => true, 'age' => 35],
        ['name' => 'Fiona', 'email' => 'fiona@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 28],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('renders with filters', function () {
    Livewire::test(TestFilterTable::class)
        ->assertSee('Alice')
        ->assertSee('Bob');
});

it('initializes filters as empty array', function () {
    Livewire::test(TestFilterTable::class)
        ->assertSet('filters', []);
});

it('filters by text (LIKE)', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.name', 'Ali')
        ->assertSee('Alice')
        ->assertDontSee('Bob')
        ->assertDontSee('Charlie');
});

it('filters by select (exact match)', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid')
        ->assertSee('Alice')
        ->assertSee('Charlie')
        ->assertDontSee('Bob')
        ->assertDontSee('Diana');
});

it('filters by multi-select (WHERE IN)', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.cities', ['Madrid', 'Sevilla'])
        ->assertSee('Alice')
        ->assertSee('Charlie')
        ->assertSee('Diana')
        ->assertDontSee('Bob')
        ->assertDontSee('Fiona');
});

it('filters by boolean true', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.is_active', '1')
        ->assertSee('Alice')
        ->assertSee('Charlie')
        ->assertSee('Diana')
        ->assertDontSee('Bob')
        ->assertDontSee('Fiona');
});

it('filters by boolean false', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.is_active', '0')
        ->assertSee('Bob')
        ->assertSee('Fiona')
        ->assertDontSee('Alice')
        ->assertDontSee('Charlie');
});

it('boolean filter with empty string shows all', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.is_active', '')
        ->assertSee('Alice')
        ->assertSee('Bob');
});

it('filters by number with operator', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.min_age', 35)
        ->assertSee('Charlie')
        ->assertSee('Diana')
        ->assertDontSee('Bob')
        ->assertDontSee('Fiona');
});

it('filters by number range (min and max)', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.age_range', ['min' => 28, 'max' => 35])
        ->assertSee('Alice')
        ->assertSee('Diana')
        ->assertSee('Fiona')
        ->assertDontSee('Bob')
        ->assertDontSee('Charlie');
});

it('filters by number range (min only)', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.age_range', ['min' => 35, 'max' => null])
        ->assertSee('Charlie')
        ->assertSee('Diana')
        ->assertDontSee('Alice')
        ->assertDontSee('Bob');
});

it('filters by number range (max only)', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.age_range', ['min' => null, 'max' => 28])
        ->assertSee('Bob')
        ->assertSee('Fiona')
        ->assertDontSee('Alice')
        ->assertDontSee('Charlie');
});

it('combines multiple filters', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid')
        ->set('filters.is_active', '1')
        ->assertSee('Alice')
        ->assertSee('Charlie')
        ->assertDontSee('Bob')
        ->assertDontSee('Diana')
        ->assertDontSee('Fiona');
});

it('resets a single filter', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid')
        ->assertDontSee('Bob')
        ->call('resetFilter', 'city')
        ->assertSee('Bob');
});

it('resets all filters', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid')
        ->set('filters.is_active', '1')
        ->call('resetAllFilters')
        ->assertSet('filters', [])
        ->assertSee('Bob')
        ->assertSee('Fiona');
});

it('returns active filters', function () {
    $component = Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid');

    $activeFilters = $component->instance()->getActiveFilters();

    expect($activeFilters)->toHaveCount(1)
        ->and($activeFilters[0]['key'])->toBe('city')
        ->and($activeFilters[0]['value'])->toBe('Madrid');
});

it('returns active filter count', function () {
    $component = Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid')
        ->set('filters.is_active', '1');

    expect($component->instance()->getActiveFilterCount())->toBe(2);
});

it('returns zero active filters when no filters set', function () {
    $component = Livewire::test(TestFilterTable::class);

    expect($component->instance()->getActiveFilterCount())->toBe(0);
});

it('resolves visible filters only', function () {
    $component = Livewire::test(TestFilterTable::class);
    $resolved = $component->instance()->resolveFilters();

    expect($resolved)->toHaveCount(6);
});

it('gets filter layout from config', function () {
    $component = Livewire::test(TestFilterTable::class);

    expect($component->instance()->getFilterLayout())->toBe('popover');
});

it('can set custom filter layout', function () {
    $component = Livewire::test(TestFilterTable::class);
    $component->instance()->setFilterLayout('inline');

    expect($component->instance()->getFilterLayout())->toBe('inline');
});

it('search and filters work together', function () {
    Livewire::test(TestFilterTable::class)
        ->set('search', 'Al')
        ->set('filters.is_active', '1')
        ->assertSee('Alice')
        ->assertDontSee('Bob')
        ->assertDontSee('Charlie');
});

it('empty filter value is ignored', function () {
    Livewire::test(TestFilterTable::class)
        ->set('filters.name', '')
        ->assertSee('Alice')
        ->assertSee('Bob')
        ->assertSee('Charlie');
});
