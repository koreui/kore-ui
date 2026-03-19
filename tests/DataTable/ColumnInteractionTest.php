<?php

use KoreUi\DataTable\Columns\Column;

it('supports copyable fluent API', function () {
    $column = Column::make('Email', 'email')->copyable();

    expect($column->isCopyable())->toBeTrue()
        ->and($column->toArray()['copyable'])->toBeTrue();
});

it('is not copyable by default', function () {
    $column = Column::make('Email', 'email');

    expect($column->isCopyable())->toBeFalse();
});

it('supports clickable with URL string', function () {
    $column = Column::make('Ciudad', 'city')->clickable('/cities');

    expect($column->isClickable())->toBeTrue()
        ->and($column->getClickableUrl(null))->toBe('/cities')
        ->and($column->isClickableNewTab())->toBeFalse();
});

it('supports clickable with callback', function () {
    $column = Column::make('Ciudad', 'city')
        ->clickable(fn ($row) => '/users/' . data_get($row, 'id'));

    expect($column->isClickable())->toBeTrue();

    $row = (object) ['id' => 42];
    expect($column->getClickableUrl($row))->toBe('/users/42');
});

it('supports clickable with new tab', function () {
    $column = Column::make('Ciudad', 'city')
        ->clickable('/cities', newTab: true);

    expect($column->isClickableNewTab())->toBeTrue();
});

it('is not clickable by default', function () {
    $column = Column::make('Ciudad', 'city');

    expect($column->isClickable())->toBeFalse();
});

it('includes copyable and clickable in toArray', function () {
    $column = Column::make('Email', 'email')->copyable();

    $array = $column->toArray();

    expect($array)->toHaveKey('copyable')
        ->and($array)->toHaveKey('clickable')
        ->and($array['copyable'])->toBeTrue()
        ->and($array['clickable'])->toBeFalse();
});
