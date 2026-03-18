<?php

use KoreUi\Breadcrumbs\BreadcrumbManager;
use KoreUi\Breadcrumbs\BreadcrumbTrail;
use KoreUi\Breadcrumbs\Exceptions\DuplicateBreadcrumbException;
use KoreUi\Breadcrumbs\Exceptions\InvalidBreadcrumbException;
use KoreUi\Breadcrumbs\Exceptions\UnnamedRouteException;

beforeEach(function () {
    $this->manager = app(BreadcrumbManager::class);
});

it('registers and generates breadcrumbs for a route', function () {
    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail
        ->push('Home', '/')
    );

    $items = $this->manager->generate('home');

    expect($items)->toHaveCount(1)
        ->and($items->first()->label)->toBe('Home')
        ->and($items->first()->url)->toBe('/')
        ->and($items->last()->current)->toBeTrue();
});

it('resolves parent breadcrumbs recursively', function () {
    $this->manager
        ->for('home', fn (BreadcrumbTrail $trail) => $trail
            ->push('Home', '/')
        )
        ->for('users.index', fn (BreadcrumbTrail $trail) => $trail
            ->parent('home')
            ->push('Users', '/users')
        )
        ->for('users.show', fn (BreadcrumbTrail $trail) => $trail
            ->parent('users.index')
            ->push('John Doe')
        );

    $items = $this->manager->generate('users.show');

    expect($items)->toHaveCount(3)
        ->and($items[0]->label)->toBe('Home')
        ->and($items[1]->label)->toBe('Users')
        ->and($items[2]->label)->toBe('John Doe')
        ->and($items[2]->current)->toBeTrue();
});

it('passes parameters to the breadcrumb callback', function () {
    $this->manager->for('users.show', fn (BreadcrumbTrail $trail, string $name) => $trail
        ->push($name)
    );

    $items = $this->manager->generate('users.show', 'Jane');

    expect($items->first()->label)->toBe('Jane');
});

it('throws DuplicateBreadcrumbException when registering same route twice', function () {
    config()->set('kore-ui.breadcrumbs.exceptions.duplicate', true);

    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail->push('Home', '/'));

    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail->push('Home 2', '/'));
})->throws(DuplicateBreadcrumbException::class);

it('silences DuplicateBreadcrumbException when configured', function () {
    config()->set('kore-ui.breadcrumbs.exceptions.duplicate', false);

    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail->push('Home', '/'));
    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail->push('Home 2', '/'));

    $items = $this->manager->generate('home');

    expect($items->first()->label)->toBe('Home 2');
});

it('throws InvalidBreadcrumbException for undefined route', function () {
    config()->set('kore-ui.breadcrumbs.exceptions.missing', true);

    $this->manager->generate('nonexistent');
})->throws(InvalidBreadcrumbException::class);

it('silences InvalidBreadcrumbException when configured', function () {
    config()->set('kore-ui.breadcrumbs.exceptions.missing', false);

    $items = $this->manager->generate('nonexistent');

    expect($items)->toBeEmpty();
});

it('checks if a breadcrumb exists for a route', function () {
    $this->manager->for('home', fn (BreadcrumbTrail $trail) => $trail->push('Home', '/'));

    expect($this->manager->exists('home'))->toBeTrue()
        ->and($this->manager->exists('nonexistent'))->toBeFalse();
});

it('executes before callbacks', function () {
    $this->manager
        ->before(fn (BreadcrumbTrail $trail) => $trail->push('Home', '/'))
        ->for('users.index', fn (BreadcrumbTrail $trail) => $trail
            ->push('Users', '/users')
        );

    $items = $this->manager->generate('users.index');

    expect($items)->toHaveCount(2)
        ->and($items[0]->label)->toBe('Home')
        ->and($items[1]->label)->toBe('Users');
});

it('executes after callbacks', function () {
    $this->manager
        ->after(fn (BreadcrumbTrail $trail) => $trail->push('Suffix'))
        ->for('home', fn (BreadcrumbTrail $trail) => $trail
            ->push('Home', '/')
        );

    $items = $this->manager->generate('home');

    expect($items)->toHaveCount(2)
        ->and($items[0]->label)->toBe('Home')
        ->and($items[1]->label)->toBe('Suffix')
        ->and($items[1]->current)->toBeTrue();
});

it('supports macros via Macroable', function () {
    BreadcrumbManager::macro('resource', function (string $name, string $label) {
        $this->for("{$name}.index", fn (BreadcrumbTrail $trail) => $trail
            ->push($label, "/{$name}")
        );
        $this->for("{$name}.create", fn (BreadcrumbTrail $trail) => $trail
            ->parent("{$name}.index")
            ->push('Create')
        );
    });

    $this->manager->resource('posts', 'Posts');

    $items = $this->manager->generate('posts.create');

    expect($items)->toHaveCount(2)
        ->and($items[0]->label)->toBe('Posts')
        ->and($items[1]->label)->toBe('Create');
});

it('overrides current route with setCurrentRoute', function () {
    $this->manager
        ->for('errors.404', fn (BreadcrumbTrail $trail) => $trail
            ->push('Home', '/')
            ->push('Not Found')
        );

    $this->manager->setCurrentRoute('errors.404');

    $items = $this->manager->generate();

    expect($items)->toHaveCount(2)
        ->and($items[1]->label)->toBe('Not Found');
});

it('clears current route override', function () {
    $this->manager->setCurrentRoute('errors.404');
    $this->manager->clearCurrentRoute();

    // After clearing, getCurrentRoute should use the actual request route
    // which in a test context has no named route
    config()->set('kore-ui.breadcrumbs.exceptions.unnamed', false);
    config()->set('kore-ui.breadcrumbs.exceptions.missing', false);

    $items = $this->manager->generate();

    expect($items)->toBeEmpty();
});

it('returns current breadcrumb (last item)', function () {
    $this->manager
        ->for('users.show', fn (BreadcrumbTrail $trail) => $trail
            ->push('Home', '/')
            ->push('Users', '/users')
            ->push('John')
        );

    $this->manager->setCurrentRoute('users.show');

    $current = $this->manager->current();

    expect($current->label)->toBe('John')
        ->and($current->current)->toBeTrue();
});

it('returns null from current() when no breadcrumbs defined', function () {
    config()->set('kore-ui.breadcrumbs.exceptions.unnamed', false);

    $current = $this->manager->current();

    expect($current)->toBeNull();
});

it('supports method chaining on for()', function () {
    $result = $this->manager
        ->for('home', fn (BreadcrumbTrail $trail) => $trail->push('Home', '/'))
        ->for('about', fn (BreadcrumbTrail $trail) => $trail->push('About', '/about'));

    expect($result)->toBeInstanceOf(BreadcrumbManager::class);
});
