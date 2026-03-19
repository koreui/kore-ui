<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\BooleanColumn;
use KoreUi\DataTable\Columns\Column;
use KoreUi\Tests\DataTable\Fixtures\TestEditableTable;
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

it('detects editable columns', function () {
    $component = Livewire::test(TestEditableTable::class);

    expect($component->instance()->hasEditableColumns())->toBeTrue();
});

it('returns editable columns map', function () {
    $component = Livewire::test(TestEditableTable::class);
    $map = $component->instance()->getEditableColumnsMap();

    expect($map)->toHaveKey('name')
        ->and($map)->toHaveKey('email')
        ->and($map)->toHaveKey('is_active')
        ->and($map['name']['component'])->toBe('input')
        ->and($map['name']['rules'])->toBe(['required', 'min:2'])
        ->and($map['is_active']['component'])->toBe('toggle');
});

it('updates cell value', function () {
    Livewire::test(TestEditableTable::class)
        ->call('updateCell', 1, 'name', 'Alice Updated')
        ->assertDispatched('kore:datatable-edit-success');

    expect(TestUser::find(1)->name)->toBe('Alice Updated');
});

it('validates cell value with rules', function () {
    Livewire::test(TestEditableTable::class)
        ->call('updateCell', 1, 'name', 'A')
        ->assertDispatched('kore:datatable-edit-error');

    // Name should remain unchanged
    expect(TestUser::find(1)->name)->toBe('Alice');
});

it('updates boolean cell value', function () {
    Livewire::test(TestEditableTable::class)
        ->call('updateCell', 1, 'is_active', false)
        ->assertDispatched('kore:datatable-edit-success');

    expect(TestUser::find(1)->is_active)->toBeFalse();
});

it('ignores non-editable fields', function () {
    Livewire::test(TestEditableTable::class)
        ->call('updateCell', 1, 'city', 'Barcelona');

    // City is not editable, should remain unchanged
    expect(TestUser::find(1)->city)->toBe('Madrid');
});

it('column editable fluent API', function () {
    $column = Column::make('Name', 'name')
        ->editable()
        ->editableRules(['required'])
        ->editableComponent('textarea');

    expect($column->isEditable())->toBeTrue()
        ->and($column->getEditableComponent())->toBe('textarea')
        ->and($column->getEditableRules())->toBe(['required']);
});

it('boolean column sets toggle component automatically', function () {
    $column = BooleanColumn::make('Active', 'is_active')->editable();

    expect($column->isEditable())->toBeTrue()
        ->and($column->getEditableComponent())->toBe('toggle');
});

it('supports editable callback', function () {
    $called = false;
    $column = Column::make('Name', 'name')
        ->editable()
        ->editableCallback(function ($rowId, $field, $value) use (&$called) {
            $called = true;
        });

    expect($column->getEditableCallback())->not->toBeNull();
});

it('renders editable cells with data attributes', function () {
    Livewire::test(TestEditableTable::class)
        ->assertSee('data-row-id');
});
