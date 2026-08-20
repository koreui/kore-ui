<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Events\BulkActionExecuted;
use KoreUi\DataTable\Events\FilterApplied;
use KoreUi\DataTable\Events\RowUpdated;
use KoreUi\Tests\DataTable\Fixtures\TestBulkTable;
use KoreUi\Tests\DataTable\Fixtures\TestEditableTable;
use KoreUi\Tests\DataTable\Fixtures\TestFilterTable;
use KoreUi\Tests\DataTable\Fixtures\TestUser;
use Livewire\Livewire;

beforeEach(function () {
    Schema::create('test_users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('city')->nullable();
        $table->string('role')->default('user');
        $table->boolean('is_active')->default(true);
        $table->integer('age')->nullable();
    });

    TestUser::insert([
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'role' => 'admin', 'is_active' => true, 'age' => 30],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'role' => 'user', 'is_active' => false, 'age' => 25],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('dispatches RowUpdated event on inline edit', function () {
    Event::fake([RowUpdated::class]);

    Livewire::test(TestEditableTable::class)
        ->call('updateCell', 1, 'name', 'Alice Updated');

    Event::assertDispatched(RowUpdated::class, function ($event) {
        return $event->tableClass === TestEditableTable::class
            && $event->rowId == 1
            && $event->field === 'name'
            && $event->value === 'Alice Updated'
            && $event->oldValue === 'Alice';
    });
});

it('dispatches BulkActionExecuted event on bulk action', function () {
    Event::fake([BulkActionExecuted::class]);

    Livewire::test(TestBulkTable::class)
        ->call('executeBulkAction', 'activate', [1, 2]);

    Event::assertDispatched(BulkActionExecuted::class, function ($event) {
        return $event->tableClass === TestBulkTable::class
            && $event->action === 'activate'
            // resolveAuthorizedIds() normaliza a string, igual que ya hacían
            // getAllMatchingIds() y getRowIds().
            && $event->ids === ['1', '2']
            && $event->count === 2;
    });
});

it('dispatches FilterApplied event when filters change', function () {
    Event::fake([FilterApplied::class]);

    Livewire::test(TestFilterTable::class)
        ->set('filters.city', 'Madrid');

    Event::assertDispatched(FilterApplied::class, function ($event) {
        return $event->tableClass === TestFilterTable::class
            && $event->filters['city'] === 'Madrid';
    });
});
