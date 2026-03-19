<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
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
    ]);
});

afterEach(function () {
    Schema::dropIfExists('test_users');
});

it('defaults to scroll responsive mode', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->viewData('responsiveMode'))->toBe('scroll');
});

it('passes responsive config to view', function () {
    $component = Livewire::test(TestTable::class);

    expect($component->viewData('responsiveMode'))->toBe('scroll')
        ->and($component->viewData('responsiveBreakpoint'))->toBe(768);
});

it('passes collapsed columns to view', function () {
    $component = Livewire::test(TestTable::class);

    $collapsedColumns = $component->viewData('collapsedColumns');
    expect($collapsedColumns)->toBeArray();
});

it('column supports collapseOnMobile', function () {
    $column = Column::make('Ciudad', 'city')->collapseOnMobile();

    expect($column->isCollapsedOnMobile())->toBeTrue()
        ->and($column->isCollapsedOnTablet())->toBeFalse();
});

it('column supports collapseOnTablet', function () {
    $column = Column::make('Email', 'email')->collapseOnTablet();

    expect($column->isCollapsedOnTablet())->toBeTrue()
        ->and($column->isCollapsedOnMobile())->toBeFalse();
});
