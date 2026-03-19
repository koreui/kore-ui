<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
use KoreUi\DataTable\Columns\NumberColumn;
use KoreUi\Tests\DataTable\Fixtures\TestAggregationTable;
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
        $table->decimal('salary', 10, 2)->nullable();
    });

    TestUser::insert([
        ['name' => 'Alice', 'email' => 'alice@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 30, 'salary' => 50000],
        ['name' => 'Bob', 'email' => 'bob@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 25, 'salary' => 40000],
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'city' => 'Madrid', 'is_active' => true, 'age' => 45, 'salary' => 60000],
        ['name' => 'Diana', 'email' => 'diana@test.com', 'city' => 'Sevilla', 'is_active' => true, 'age' => 35, 'salary' => 55000],
        ['name' => 'Fiona', 'email' => 'fiona@test.com', 'city' => 'Barcelona', 'is_active' => false, 'age' => 28, 'salary' => 45000],
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('detects columns with aggregation', function () {
    $component = Livewire::test(TestAggregationTable::class);
    expect($component->instance()->hasAnyAggregation())->toBeTrue();
});

it('computes sum aggregation', function () {
    $component = Livewire::test(TestAggregationTable::class);
    $columns = $component->instance()->resolveColumns();
    $aggregations = $component->instance()->getAggregations($columns);

    expect($aggregations)->toHaveKey('age')
        ->and($aggregations['age']['value'])->toBe('163')
        ->and($aggregations['age']['label'])->toBe('Total');
});

it('computes avg aggregation', function () {
    $component = Livewire::test(TestAggregationTable::class);
    $columns = $component->instance()->resolveColumns();
    $aggregations = $component->instance()->getAggregations($columns);

    expect($aggregations)->toHaveKey('salary')
        ->and($aggregations['salary']['value'])->toContain('50')
        ->and($aggregations['salary']['label'])->toBe('Promedio');
});

it('respects search when computing aggregations', function () {
    $component = Livewire::test(TestAggregationTable::class)
        ->set('search', 'Alice');

    $columns = $component->instance()->resolveColumns();
    $aggregations = $component->instance()->getAggregations($columns);

    expect($aggregations['age']['value'])->toBe('30');
});

it('renders tfoot in HTML', function () {
    Livewire::test(TestAggregationTable::class)
        ->assertSee('Total')
        ->assertSee('Promedio');
});

it('column has sum fluent API', function () {
    $column = Column::make('Age', 'age')->sum('Total');

    expect($column->hasAggregation())->toBeTrue()
        ->and($column->getAggregationType())->toBe('sum')
        ->and($column->getFooterLabel())->toBe('Total');
});

it('column has avg fluent API', function () {
    $column = NumberColumn::make('Salary', 'salary')->avg(2, 'Promedio');

    expect($column->hasAggregation())->toBeTrue()
        ->and($column->getAggregationType())->toBe('avg')
        ->and($column->getAggregationDecimals())->toBe(2)
        ->and($column->getFooterLabel())->toBe('Promedio');
});

it('column has count fluent API', function () {
    $column = Column::make('ID', 'id')->count('Registros');

    expect($column->hasAggregation())->toBeTrue()
        ->and($column->getAggregationType())->toBe('count')
        ->and($column->getFooterLabel())->toBe('Registros');
});

it('column has min/max fluent API', function () {
    $min = Column::make('Age', 'age')->footerMin();
    $max = Column::make('Age', 'age')->footerMax();

    expect($min->getAggregationType())->toBe('min')
        ->and($max->getAggregationType())->toBe('max');
});

it('supports custom footer callback', function () {
    $column = Column::make('Age', 'age')
        ->footer(fn ($query) => $query->count() . ' registros')
        ->footerLabel('Info');

    expect($column->hasAggregation())->toBeTrue()
        ->and($column->getFooterCallback())->not->toBeNull();
});
