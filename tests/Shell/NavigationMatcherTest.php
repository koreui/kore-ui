<?php

use Illuminate\Support\Facades\Route;
use KoreUi\Shell\NavigationMatcher;

beforeEach(function () {
    Route::get('/users', fn () => '')->name('users.index');
    Route::get('/users/{user}', fn () => '')->name('users.show');
    Route::get('/dashboard', fn () => '')->name('dashboard');

    // ->name() se aplica DESPUÉS de que la ruta entre en la colección, así que el
    // índice de nombres todavía no las conoce y Route::has() daría false.
    Route::getRoutes()->refreshNameLookups();
});

// --- Resolución del href ---

it('resolves a route name to a URL', function () {
    expect(NavigationMatcher::href(route: 'users.index'))->toBe(url('/users'));
});

it('resolves a route with parameters', function () {
    expect(NavigationMatcher::href(route: 'users.show', routeParams: ['user' => 7]))->toBe(url('/users/7'));
});

it('falls back to href when the route name is a wildcard pattern', function () {
    // La spec propone route="users.*", que NO es un nombre resoluble: solo sirve
    // para matching. Sin esta guarda, route() lanzaría y reventaría el layout entero.
    expect(NavigationMatcher::href(route: 'users.*', href: '/users'))->toBe('/users');
});

it('falls back to href when the route does not exist', function () {
    expect(NavigationMatcher::href(route: 'does.not.exist', href: '/fallback'))->toBe('/fallback');
});

it('falls back to href when the route is missing required parameters', function () {
    // users.show necesita {user}: sin él, route() lanza UrlGenerationException.
    // Un enlace mal escrito no puede tumbar la navegación entera.
    expect(NavigationMatcher::href(route: 'users.show', href: '/safe'))->toBe('/safe');
});

it('returns the plain href when no route is given', function () {
    expect(NavigationMatcher::href(href: 'https://example.com'))->toBe('https://example.com')
        ->and(NavigationMatcher::href())->toBeNull();
});

// --- Ruta activa: prioridad ---

it('lets an explicit active prop override everything, in both directions', function () {
    $this->get('/users');

    expect(NavigationMatcher::isActive(active: true, route: 'dashboard'))->toBeTrue()
        ->and(NavigationMatcher::isActive(active: false, route: 'users.index'))->toBeFalse()
        ->and(NavigationMatcher::isActive(active: false, hasActiveChild: true))->toBeFalse();
});

it('matches on the match pattern before the route', function () {
    $this->get('/users/3');

    expect(NavigationMatcher::isActive(match: 'users.*'))->toBeTrue()
        ->and(NavigationMatcher::isActive(match: 'settings.*'))->toBeFalse();
});

it('accepts several match patterns', function () {
    $this->get('/dashboard');

    expect(NavigationMatcher::isActive(match: ['settings.*', 'dashboard']))->toBeTrue();
});

it('matches on the route name', function () {
    $this->get('/dashboard');

    expect(NavigationMatcher::isActive(route: 'dashboard'))->toBeTrue()
        ->and(NavigationMatcher::isActive(route: 'users.index'))->toBeFalse();
});

it('matches a route name with a wildcard', function () {
    $this->get('/users/3');

    expect(NavigationMatcher::isActive(route: 'users.*'))->toBeTrue();
});

it('matches on the href when there is no route', function () {
    $this->get('/dashboard');

    expect(NavigationMatcher::isActive(href: '/dashboard'))->toBeTrue()
        ->and(NavigationMatcher::isActive(href: '/users'))->toBeFalse();
});

it('marks the parent active when a child is active', function () {
    $this->get('/dashboard');

    // El padre no apunta a la ruta actual, pero uno de sus sub-items sí.
    expect(NavigationMatcher::isActive(route: 'users.index', hasActiveChild: true))->toBeTrue();
});

it('still honours an active child when smart routing is off', function () {
    $this->get('/dashboard');

    expect(NavigationMatcher::isActive(route: 'dashboard', smart: false))->toBeFalse()
        ->and(NavigationMatcher::isActive(route: 'dashboard', smart: false, hasActiveChild: true))->toBeTrue();
});

it('is not active when nothing is given', function () {
    $this->get('/dashboard');

    expect(NavigationMatcher::isActive())->toBeFalse();
});

// --- Comparación de URLs ---

it('ignores trailing slashes when comparing URLs', function () {
    $this->get('/dashboard');

    expect(NavigationMatcher::matchesUrl('/dashboard/'))->toBeTrue();
});

it('supports a wildcard in the href', function () {
    $this->get('/users/3');

    expect(NavigationMatcher::matchesUrl('/users/*'))->toBeTrue()
        ->and(NavigationMatcher::matchesUrl('/settings/*'))->toBeFalse();
});

it('never marks a non-navigating link as active', function () {
    $this->get('/dashboard');

    // Un "#" o un mailto: no llevan a ninguna página, así que no pueden ser la actual.
    expect(NavigationMatcher::matchesUrl('#'))->toBeFalse()
        ->and(NavigationMatcher::matchesUrl(''))->toBeFalse()
        ->and(NavigationMatcher::matchesUrl('mailto:a@b.com'))->toBeFalse()
        ->and(NavigationMatcher::matchesUrl('javascript:alert(1)'))->toBeFalse();
});
