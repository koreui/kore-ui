<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\Tests\DataTable\Fixtures\TestQueryStringTable;
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

it('enables query string via configure', function () {
    $component = Livewire::test(TestQueryStringTable::class);

    expect($component->instance()->isQueryStringEnabled())->toBeTrue();
});

it('returns query string mapping when enabled', function () {
    $component = Livewire::test(TestQueryStringTable::class);
    $qs = $component->instance()->queryString();

    expect($qs)->toHaveKey('search')
        ->and($qs['search']['as'])->toBe('q')
        ->and($qs)->toHaveKey('sorts')
        ->and($qs)->toHaveKey('filters')
        ->and($qs)->toHaveKey('perPage');
});

it('returns empty query string when disabled', function () {
    $component = Livewire::test(TestTable::class);
    $qs = $component->instance()->queryString();

    expect($qs)->toBe([]);
});

it('disabled by default', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->isQueryStringEnabled())->toBeFalse();
});
