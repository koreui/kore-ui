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

    // 25 rows so that perPage=10 (an allowed option) yields 3 pages.
    TestUser::insert(collect(range(1, 25))->map(fn ($i) => [
        'name'      => "User {$i}",
        'email'     => "u{$i}@test.com",
        'city'      => 'Madrid',
        'is_active' => true,
        'age'       => 20 + $i,
    ])->all());
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

// --- urlKey / prefix --------------------------------------------------------

it('leaves URL keys unprefixed when no table name is set', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->instance()->urlKey('page'))->toBe('page')
        ->and($component->instance()->urlKey('per_page'))->toBe('per_page')
        ->and($component->instance()->pageName())->toBe('page')
        ->and($component->instance()->tablePrefix())->toBe('');
});

it('namespaces URL keys with the table name', function () {
    $component = Livewire::test(TestTable::class, ['tableName' => 'users']);

    expect($component->instance()->urlKey('page'))->toBe('users_page')
        ->and($component->instance()->urlKey('per_page'))->toBe('users_per_page')
        ->and($component->instance()->pageName())->toBe('users_page');
});

it('sanitizes the table name into a URL-safe prefix', function () {
    expect(Livewire::test(TestTable::class, ['tableName' => 'Users Table'])->instance()->tablePrefix())
        ->toBe('users_table');

    expect(Livewire::test(TestTable::class, ['tableName' => 'orders-2!'])->instance()->urlKey('q'))
        ->toBe('orders_2_q');
});

// --- queryString aliases ----------------------------------------------------

it('prefixes every queryString alias when a table name is set', function () {
    $qs = Livewire::test(TestQueryStringTable::class, ['tableName' => 'users'])
        ->instance()
        ->queryString();

    expect($qs['perPage']['as'])->toBe('users_per_page')
        ->and($qs['search']['as'])->toBe('users_q')
        ->and($qs['sorts']['as'])->toBe('users_sort')
        ->and($qs['filters']['as'])->toBe('users_filter');
});

it('gives two tables disjoint URL keys', function () {
    $users  = Livewire::test(TestQueryStringTable::class, ['tableName' => 'users'])->instance();
    $orders = Livewire::test(TestQueryStringTable::class, ['tableName' => 'orders'])->instance();

    $usersKeys  = collect($users->queryString())->pluck('as')->all();
    $ordersKeys = collect($orders->queryString())->pluck('as')->all();

    expect(array_intersect($usersKeys, $ordersKeys))->toBeEmpty();
});

// --- reading the right param from the URL ------------------------------------

it('reads perPage from its own prefixed URL key', function () {
    Livewire::withQueryParams(['users_per_page' => 10])
        ->test(TestTable::class, ['tableName' => 'users'])
        ->assertSet('perPage', 10);
});

it('ignores the unprefixed per_page when a table name is set', function () {
    // A sibling table's (or a single-table) ?per_page must not leak in.
    Livewire::withQueryParams(['per_page' => 10])
        ->test(TestTable::class, ['tableName' => 'users'])
        ->assertSet('perPage', 25);
});

it('restores its page from its own prefixed URL key', function () {
    $component = Livewire::withQueryParams(['users_page' => 2, 'users_per_page' => 10])
        ->test(TestTable::class, ['tableName' => 'users']);

    expect($component->instance()->getPage())->toBe(2);
});

it('ignores an unprefixed page when a table name is set', function () {
    $component = Livewire::withQueryParams(['page' => 2, 'users_per_page' => 10])
        ->test(TestTable::class, ['tableName' => 'users']);

    expect($component->instance()->getPage())->toBe(1);
});

// --- pagination still works through the override ----------------------------

it('paginates through the prefixed page key', function () {
    $component = Livewire::test(TestTable::class, ['tableName' => 'users'])
        ->set('perPage', 10);

    expect($component->instance()->getPage())->toBe(1);

    $component->call('nextPage');
    expect($component->instance()->getPage())->toBe(2);

    $component->call('gotoPage', 3);
    expect($component->instance()->getPage())->toBe(3);

    $component->call('previousPage');
    expect($component->instance()->getPage())->toBe(2);

    $component->call('resetPage');
    expect($component->instance()->getPage())->toBe(1);
});
