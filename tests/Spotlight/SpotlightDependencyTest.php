<?php

use KoreUi\Spotlight\SpotlightDependency;

// --- Search dependency ---

it('creates a search dependency', function () {
    $dep = SpotlightDependency::search('Seleccionar usuario', 'users.spotlight');

    expect($dep->getType())->toBe('search')
        ->and($dep->getPlaceholder())->toBe('Seleccionar usuario')
        ->and($dep->getSearchUrl())->toBe('users.spotlight');
});

it('search dependency defaults to GET method', function () {
    $data = SpotlightDependency::search('Test', 'test.route')->toArray();

    expect($data['searchMethod'])->toBe('GET');
});

it('search dependency accepts POST method', function () {
    $data = SpotlightDependency::search('Test', 'test.route', 'post')->toArray();

    expect($data['searchMethod'])->toBe('POST');
});

it('search dependency normalizes method to uppercase', function () {
    $data = SpotlightDependency::search('Test', 'test.route', 'post')->toArray();

    expect($data['searchMethod'])->toBe('POST');
});

// --- Input dependency ---

it('creates an input dependency', function () {
    $dep = SpotlightDependency::input('Motivo');

    expect($dep->getType())->toBe('input')
        ->and($dep->getPlaceholder())->toBe('Motivo');
});

it('input dependency accepts validation rule', function () {
    $data = SpotlightDependency::input('Motivo', 'min:3|max:255')->toArray();

    expect($data['validation'])->toBe('min:3|max:255');
});

it('input dependency has null validation by default', function () {
    $data = SpotlightDependency::input('Test')->toArray();

    expect($data['validation'])->toBeNull();
});

// --- Select dependency ---

it('creates a select dependency', function () {
    $options = ['admin' => 'Administrador', 'editor' => 'Editor'];
    $dep = SpotlightDependency::select('Seleccionar rol', $options);

    expect($dep->getType())->toBe('select')
        ->and($dep->getOptions())->toBe($options);
});

it('select dependency stores options correctly', function () {
    $options = ['a' => 'Option A', 'b' => 'Option B', 'c' => 'Option C'];
    $data = SpotlightDependency::select('Elegir', $options)->toArray();

    expect($data['options'])->toBe($options)
        ->and($data['options'])->toHaveCount(3);
});

// --- toArray completeness ---

it('search dependency includes all keys in toArray', function () {
    $data = SpotlightDependency::search('Test', 'test.route')->toArray();

    expect($data)->toHaveKeys([
        'type', 'placeholder', 'searchUrl', 'searchMethod', 'validation', 'options',
    ]);
});

it('input dependency has null searchUrl in toArray', function () {
    $data = SpotlightDependency::input('Test')->toArray();

    expect($data['searchUrl'])->toBeNull()
        ->and($data['options'])->toBe([]);
});

it('select dependency has null searchUrl in toArray', function () {
    $data = SpotlightDependency::select('Test', ['a' => 'A'])->toArray();

    expect($data['searchUrl'])->toBeNull()
        ->and($data['validation'])->toBeNull();
});
