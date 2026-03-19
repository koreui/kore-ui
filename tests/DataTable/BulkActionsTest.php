<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestBulkTable;
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
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 45],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('has bulk actions', function () {
    $component = Livewire::test(TestBulkTable::class);

    expect($component->instance()->hasBulkActions())->toBeTrue();
});

it('table without bulk actions returns false', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->hasBulkActions())->toBeFalse();
});

it('resolves visible bulk actions', function () {
    $component = Livewire::test(TestBulkTable::class);
    $actions = $component->instance()->resolveBulkActions();

    expect($actions)->toHaveCount(2);
});

it('executes bulk action without confirm', function () {
    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'activate', ['1', '2'])
        ->assertDispatched('kore:datatable-clear-selection');

    expect(TestUser::where('is_active', true)->count())->toBe(3);
});

it('executes bulk action with confirm dispatches confirm event', function () {
    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'delete', ['1', '2'])
        ->assertDispatched('kore:open');
});

it('confirmBulkAction executes the action', function () {
    Livewire::test(TestBulkTable::class)
        ->call('confirmBulkAction', 'delete', ['1', '2'])
        ->assertDispatched('kore:datatable-clear-selection');

    expect(TestUser::count())->toBe(1);
});

it('clears selected after action', function () {
    $component = Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'activate', ['1', '2']);

    expect($component->instance()->getSelected())->toBe([]);
});

it('ignores unknown bulk action identifier', function () {
    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'nonexistent', ['1', '2'])
        ->assertNotDispatched('kore:datatable-clear-selection');
});

it('selection is disabled when no bulk actions', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->isSelectionEnabled())->toBeFalse();
});

it('does not render checkboxes when no bulk actions', function () {
    Livewire::test(TestTable::class)
        ->assertDontSeeHtml('x-on:change="toggleAll()"');
});

it('bulk action delete removes records', function () {
    Livewire::test(TestBulkTable::class)
        ->call('confirmBulkAction', 'delete', ['1', '3']);

    expect(TestUser::count())->toBe(1)
        ->and(TestUser::first()->name)->toBe('Bob');
});

it('bulk action activate updates records', function () {
    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'activate', ['2']);

    expect(TestUser::find(2)->is_active)->toBeTrue();
});

it('confirm message supports :count placeholder', function () {
    // The confirm flow dispatches the event with the message
    // We verify indirectly that the action resolves properly
    $component = Livewire::test(TestBulkTable::class);
    $actions = $component->instance()->resolveBulkActions();
    $deleteAction = collect($actions)->first(fn ($a) => $a->getIdentifier() === 'delete');

    expect($deleteAction->getConfirmMessage())->toContain(':count');
});

it('renders bulk actions dropdown', function () {
    Livewire::test(TestBulkTable::class)
        ->assertSee('Activar')
        ->assertSee('Eliminar');
});

it('dispatches toast after bulk action', function () {
    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'activate', ['1'])
        ->assertDispatched('kore:toast');
});

it('dispatches clear selection event after action', function () {
    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'activate', ['1', '2'])
        ->assertDispatched('kore:datatable-clear-selection');
});
