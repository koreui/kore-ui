<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestBulkTable;
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

it('selection is enabled when bulk actions exist', function () {
    $component = Livewire::test(TestBulkTable::class);

    expect($component->instance()->isSelectionEnabled())->toBeTrue();
});

it('renders checkboxes when selection is enabled', function () {
    Livewire::test(TestBulkTable::class)
        ->assertSeeHtml('type="checkbox"');
});

it('returns row IDs for current page', function () {
    $component = Livewire::test(TestBulkTable::class);
    $rows = $component->instance()->getRows();
    $ids = $component->instance()->getRowIds($rows);

    expect($ids)->toHaveCount(3)
        ->and($ids)->each->toBeString();
});

it('uses custom primary key', function () {
    $component = Livewire::test(TestBulkTable::class);
    $component->instance()->setPrimaryKey('email');

    expect($component->instance()->getPrimaryKey())->toBe('email');
});

it('default primary key is id', function () {
    $component = Livewire::test(TestBulkTable::class);

    expect($component->instance()->getPrimaryKey())->toBe('id');
});

it('can disable selection', function () {
    $component = Livewire::test(TestBulkTable::class);
    $component->instance()->setSelectionEnabled(false);

    expect($component->instance()->isSelectionEnabled())->toBeFalse();
});

it('passes rowIds to view', function () {
    Livewire::test(TestBulkTable::class)
        ->assertSeeHtml('rowIds');
});

it('checkbox column adjusts empty state colspan', function () {
    Schema::dropIfExists('test_users');
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('age')->nullable();
    });

    Livewire::test(TestBulkTable::class)
        ->assertSeeHtml('colspan="4"');
});
