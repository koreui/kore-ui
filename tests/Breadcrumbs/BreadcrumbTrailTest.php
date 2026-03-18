<?php

use KoreUi\Breadcrumbs\BreadcrumbManager;
use KoreUi\Breadcrumbs\BreadcrumbTrail;

beforeEach(function () {
    $this->manager = app(BreadcrumbManager::class);
    $this->trail = new BreadcrumbTrail($this->manager);
});

it('pushes items with all properties', function () {
    $this->trail->push('Home', '/', icon: 'home', tooltip: 'Go home', data: ['badge' => 'new']);

    $item = $this->trail->items()[0];

    expect($item->label)->toBe('Home')
        ->and($item->url)->toBe('/')
        ->and($item->icon)->toBe('home')
        ->and($item->tooltip)->toBe('Go home')
        ->and($item->current)->toBeFalse()
        ->and($item->badge)->toBe('new');
});

it('supports method chaining', function () {
    $result = $this->trail
        ->push('Home', '/')
        ->push('Users', '/users')
        ->push('Detail');

    expect($result)->toBeInstanceOf(BreadcrumbTrail::class)
        ->and($this->trail->items())->toHaveCount(3);
});

it('returns items as array', function () {
    $this->trail->push('A')->push('B');

    $items = $this->trail->items();

    expect($items)->toBeArray()
        ->toHaveCount(2)
        ->and($items[0]->label)->toBe('A')
        ->and($items[1]->label)->toBe('B');
});

it('resolves parent items via manager', function () {
    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail
        ->push('Home', '/')
    );

    $this->trail->parent('home')->push('Page');

    $items = $this->trail->items();

    expect($items)->toHaveCount(2)
        ->and($items[0]->label)->toBe('Home')
        ->and($items[1]->label)->toBe('Page');
});

it('detects circular parent references', function () {
    $this->manager
        ->for('a', fn (BreadcrumbTrail $trail) => $trail->parent('b')->push('A'))
        ->for('b', fn (BreadcrumbTrail $trail) => $trail->parent('a')->push('B'));

    $this->trail->parent('a');
})->throws(RuntimeException::class, 'Circular breadcrumb reference');

it('pushes items with default null values', function () {
    $this->trail->push('Simple');

    $item = $this->trail->items()[0];

    expect($item->url)->toBeNull()
        ->and($item->icon)->toBeNull()
        ->and($item->tooltip)->toBeNull();
});
