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

it('persists perPage but nothing else when disabled', function () {
    $component = Livewire::test(TestTable::class);
    $qs = $component->instance()->queryString();

    // perPage always persists in the URL (consistency with `page`); search,
    // sorts and filters remain opt-in via queryString.
    expect($qs)->toHaveKey('perPage')
        ->and($qs)->not->toHaveKey('search')
        ->and($qs)->not->toHaveKey('filters')
        ->and($qs)->not->toHaveKey('sorts');
});

it('disabled by default', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->isQueryStringEnabled())->toBeFalse();
});

it('reads perPage from the per_page URL parameter on load', function () {
    // mount() must not overwrite the value BaseUrl restored from ?per_page.
    Livewire::withQueryParams(['per_page' => 10])
        ->test(TestTable::class)
        ->assertSet('perPage', 10);
});

it('coerces an out-of-range perPage from the URL to an allowed value', function () {
    // 999 isn't among the allowed options → fall back to the first one.
    Livewire::withQueryParams(['per_page' => 999])
        ->test(TestTable::class)
        ->assertSet('perPage', 10);
});
