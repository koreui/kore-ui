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

it('returns empty active sorts by default', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->getActiveSorts())->toBe([]);
});

it('returns active sorts after sorting', function () {
    $component = Livewire::test(TestTable::class)
        ->call('sortBy', 'name');

    $sorts = $component->instance()->getActiveSorts();

    expect($sorts)->toHaveCount(1)
        ->and($sorts[0]['field'])->toBe('name')
        ->and($sorts[0]['label'])->toBe('Nombre')
        ->and($sorts[0]['direction'])->toBe('asc');
});

it('removes a sort by column', function () {
    $component = Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->call('removeSortBy', 'name');

    expect($component->instance()->getActiveSorts())->toBe([]);
});

it('clears all sorts', function () {
    $component = Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->call('sortBy', 'email')
        ->call('clearSorts');

    expect($component->instance()->getActiveSorts())->toBe([])
        ->and($component->get('sorts'))->toBe([]);
});

it('renders sort pills view', function () {
    Livewire::test(TestTable::class)
        ->call('sortBy', 'name')
        ->assertSee('Ordenado por')
        ->assertSee('Nombre')
        ->assertSee('Limpiar ordenamiento');
});

it('does not render sort pills when no sorts active', function () {
    Livewire::test(TestTable::class)
        ->assertDontSee('Ordenado por');
});
