<?php

use Illuminate\Support\Facades\Schema;
use KoreUi\DataTable\Columns\Column;
use KoreUi\Tests\DataTable\Fixtures\TestCollapseTable;
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

/**
 * El botón que despliega el resto de la fila en modo `collapse` era un chevron y
 * nada más: sin nombre, un lector anunciaba «botón» y ya, y tampoco decía si la
 * fila estaba abierta o cerrada.
 */
it('nombra el botón que despliega el resto de la fila', function () {
    $component = Livewire::test(TestCollapseTable::class)->call('setViewport', true);

    $component->assertSeeHtml('aria-label="Ver el resto de la fila"')
        ->assertSeeHtml('x-bind:aria-expanded');
});

it('permite traducir ese nombre desde la configuración', function () {
    config()->set('kore-ui.datatable.translations.expand_row', 'Show the rest of the row');

    Livewire::test(TestCollapseTable::class)
        ->call('setViewport', true)
        ->assertSeeHtml('aria-label="Show the rest of the row"');
});

/**
 * «Ordenar por», «Quitar filtro» y «Quitar orden» estaban escritos en la vista,
 * pegados a la columna: la interpolación de al lado los escondía del cepo de
 * textos, así que eran los últimos que no se podían traducir.
 */
it('nombra el botón de ordenar con la columna dentro', function () {
    Livewire::test(TestTable::class)
        ->assertSeeHtml('aria-label="Ordenar por Nombre"');
});

it('permite traducir ese nombre, con la columna en otro sitio', function () {
    config()->set('kore-ui.datatable.translations.sort_by', 'Sort by :columna');

    Livewire::test(TestTable::class)
        ->assertSeeHtml('aria-label="Sort by Nombre"');
});
