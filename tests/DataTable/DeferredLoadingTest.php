<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestDeferredTable;
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

it('shows skeleton when deferred loading is enabled', function () {
    Livewire::test(TestDeferredTable::class)
        ->assertSeeHtml('wire:init="loadData"');
});

it('loads data after calling loadData', function () {
    $component = Livewire::test(TestDeferredTable::class);

    expect($component->instance()->isDataLoaded())->toBeFalse();

    $component->call('loadData');

    expect($component->instance()->isDataLoaded())->toBeTrue();
    $component->assertSee('Alice');
});

it('is not deferred by default', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->isDeferredLoading())->toBeFalse()
        ->and($component->instance()->isDataLoaded())->toBeTrue();
});
