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

it('blocks javascript: URL in clickable string', function () {
    $column = Column::make('Link', 'field')->clickable("javascript:alert('XSS')");
    expect($column->getClickableUrl(null))->toBeNull();
});

it('blocks javascript: URL returned by clickable callback', function () {
    $column = Column::make('Link', 'field')
        ->clickable(fn ($row) => "javascript:alert(1)");
    expect($column->getClickableUrl(null))->toBeNull();
});

it('blocks data: URL in clickable', function () {
    $column = Column::make('Link', 'field')
        ->clickable('data:text/html,<script>alert(1)</script>');
    expect($column->getClickableUrl(null))->toBeNull();
});

it('allows https URL in clickable', function () {
    $column = Column::make('Link', 'field')->clickable('https://example.com');
    expect($column->getClickableUrl(null))->toBe('https://example.com');
});

it('allows relative path in clickable', function () {
    $column = Column::make('Link', 'field')->clickable('/users/42');
    expect($column->getClickableUrl(null))->toBe('/users/42');
});

it('includes copyable and clickable in toArray', function () {
    $column = Column::make('Email', 'email')->copyable();

    $array = $column->toArray();

    expect($array)->toHaveKey('copyable')
        ->and($array)->toHaveKey('clickable')
        ->and($array['copyable'])->toBeTrue()
        ->and($array['clickable'])->toBeFalse();
});
