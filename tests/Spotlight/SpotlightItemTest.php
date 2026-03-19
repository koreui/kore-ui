<?php

use KoreUi\Spotlight\SpotlightDependency;
use KoreUi\Spotlight\SpotlightItem;

// --- Builder fluent API ---

it('returns same instance for chaining', function () {
    $item = SpotlightItem::make('Test');
    expect($item->icon('home'))->toBe($item);
});

it('makes an item with stable slug id', function () {
    $item = SpotlightItem::make('Crear Usuario');
    $data = $item->toArray();

    expect($data['id'])->toBe('crear-usuario')
        ->and($data['name'])->toBe('Crear Usuario');
});

it('sets description', function () {
    $data = SpotlightItem::make('Test')
        ->description('Descripción de prueba')
        ->toArray();

    expect($data['description'])->toBe('Descripción de prueba');
});

it('sets icon', function () {
    $data = SpotlightItem::make('Test')
        ->icon('user-plus')
        ->toArray();

    expect($data['icon'])->toBe('user-plus');
});

it('sets group', function () {
    $data = SpotlightItem::make('Test')
        ->group('Navegación')
        ->toArray();

    expect($data['group'])->toBe('Navegación');
});

it('has General as default group', function () {
    $data = SpotlightItem::make('Test')->toArray();

    expect($data['group'])->toBe('General');
});

it('sets shortcut', function () {
    $data = SpotlightItem::make('Test')
        ->shortcut('⌘K')
        ->toArray();

    expect($data['shortcut'])->toBe('⌘K');
});

// --- Action types ---

it('sets route action', function () {
    $data = SpotlightItem::make('Inicio')
        ->route('home')
        ->toArray();

    expect($data['actionType'])->toBe('route')
        ->and($data['actionTarget'])->toBe('home')
        ->and($data['actionParams'])->toBe([]);
});

it('sets route action with params', function () {
    $data = SpotlightItem::make('Ver usuario')
        ->route('users.show', ['id' => 42])
        ->toArray();

    expect($data['actionType'])->toBe('route')
        ->and($data['actionParams'])->toBe(['id' => 42]);
});

it('sets url action', function () {
    $data = SpotlightItem::make('Docs')
        ->url('https://example.com')
        ->toArray();

    expect($data['actionType'])->toBe('url')
        ->and($data['actionTarget'])->toBe('https://example.com');
});

it('sets action (livewire method)', function () {
    $data = SpotlightItem::make('Exportar')
        ->action('exportCsv', ['format' => 'csv'])
        ->toArray();

    expect($data['actionType'])->toBe('action')
        ->and($data['actionTarget'])->toBe('exportCsv')
        ->and($data['actionParams'])->toBe(['format' => 'csv']);
});

it('sets dispatch action', function () {
    $data = SpotlightItem::make('Abrir modal')
        ->dispatch('kore:open', ['name' => 'my-modal'])
        ->toArray();

    expect($data['actionType'])->toBe('dispatch')
        ->and($data['actionTarget'])->toBe('kore:open')
        ->and($data['actionParams'])->toBe(['name' => 'my-modal']);
});

// --- Synonyms ---

it('adds synonyms', function () {
    $data = SpotlightItem::make('Usuarios')
        ->synonym('people', 'clientes')
        ->toArray();

    expect($data['synonyms'])->toContain('people')
        ->and($data['synonyms'])->toContain('clientes');
});

it('accumulates multiple synonym calls', function () {
    $data = SpotlightItem::make('Test')
        ->synonym('a', 'b')
        ->synonym('c')
        ->toArray();

    expect($data['synonyms'])->toHaveCount(3);
});

// --- Hidden ---

it('sets hidden flag', function () {
    $data = SpotlightItem::make('Test')
        ->hidden()
        ->toArray();

    expect($data['hidden'])->toBeTrue();
});

it('is visible by default', function () {
    $data = SpotlightItem::make('Test')->toArray();

    expect($data['hidden'])->toBeFalse();
});

// --- when() ---

it('is visible when condition is true', function () {
    $item = SpotlightItem::make('Test')->when(true);

    expect($item->isVisible())->toBeTrue();
});

it('is not visible when condition is false', function () {
    $item = SpotlightItem::make('Test')->when(false);

    expect($item->isVisible())->toBeFalse();
});

// --- gate() ---

it('is visible when gate passes', function () {
    // Gate requires an authenticated user in Laravel 12
    $user = new class extends \Illuminate\Foundation\Auth\User {
        public $id = 1;
    };
    auth()->login($user);

    \Illuminate\Support\Facades\Gate::define('create-users', fn ($u) => true);

    $item = SpotlightItem::make('Test')->gate('create-users');

    expect($item->isVisible())->toBeTrue();
});

it('is not visible when gate fails', function () {
    $user = new class extends \Illuminate\Foundation\Auth\User {
        public $id = 1;
    };
    auth()->login($user);

    \Illuminate\Support\Facades\Gate::define('create-users', fn ($u) => false);

    $item = SpotlightItem::make('Test')->gate('create-users');

    expect($item->isVisible())->toBeFalse();
});

// --- Dependencies ---

it('adds a search dependency', function () {
    $dep = SpotlightDependency::search('Seleccionar usuario', 'users.spotlight');

    $data = SpotlightItem::make('Asignar ticket')
        ->dependency($dep)
        ->toArray();

    expect($data['dependencies'])->toHaveCount(1)
        ->and($data['dependencies'][0]['type'])->toBe('search')
        ->and($data['dependencies'][0]['placeholder'])->toBe('Seleccionar usuario')
        ->and($data['dependencies'][0]['searchUrl'])->toBe('users.spotlight');
});

it('adds an input dependency', function () {
    $dep = SpotlightDependency::input('Motivo', 'min:3');

    $data = SpotlightItem::make('Archivar')
        ->dependency($dep)
        ->toArray();

    expect($data['dependencies'][0]['type'])->toBe('input')
        ->and($data['dependencies'][0]['validation'])->toBe('min:3');
});

it('adds a select dependency', function () {
    $dep = SpotlightDependency::select('Seleccionar rol', [
        'admin'  => 'Administrador',
        'editor' => 'Editor',
    ]);

    $data = SpotlightItem::make('Asignar rol')
        ->dependency($dep)
        ->toArray();

    expect($data['dependencies'][0]['type'])->toBe('select')
        ->and($data['dependencies'][0]['options'])->toBe([
            'admin'  => 'Administrador',
            'editor' => 'Editor',
        ]);
});

it('allows multiple dependencies chained', function () {
    $item = SpotlightItem::make('Asignar rol a usuario')
        ->dependency(SpotlightDependency::search('Seleccionar usuario', 'users.spotlight'))
        ->dependency(SpotlightDependency::select('Seleccionar rol', ['admin' => 'Admin']));

    $data = $item->toArray();

    expect($data['dependencies'])->toHaveCount(2);
});

// --- toArray completeness ---

it('includes all required keys in toArray', function () {
    $data = SpotlightItem::make('Test')->toArray();

    expect($data)->toHaveKeys([
        'id', 'name', 'description', 'icon', 'group', 'shortcut',
        'actionType', 'actionTarget', 'actionParams', 'synonyms', 'hidden', 'dependencies',
    ]);
});
