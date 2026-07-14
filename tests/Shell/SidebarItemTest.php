<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/dashboard', fn () => '')->name('dashboard');
    Route::get('/users', fn () => '')->name('users.index');
    Route::get('/users/{user}', fn () => '')->name('users.show');
    Route::get('/settings/profile', fn () => '')->name('settings.profile');
    Route::getRoutes()->refreshNameLookups();
});

// --- Enlace ---

it('renders a link with its label and icon', function () {
    $view = $this->blade('<x-kore::sidebar.item label="Inicio" icon="home" href="/home" />');

    $view->assertSee('Inicio')
        ->assertSee('href="/home"', false)
        ->assertSee('<li', false);
});

it('resolves a route name into an href', function () {
    $view = $this->blade('<x-kore::sidebar.item label="Users" route="users.index" />');

    $view->assertSee('href="'.url('/users').'"', false);
});

it('resolves route parameters', function () {
    $view = $this->blade('<x-kore::sidebar.item label="User" route="users.show" :route-params="[\'user\' => 9]" />');

    $view->assertSee('href="'.url('/users/9').'"', false);
});

it('does not blow up when the route is missing parameters', function () {
    // Un enlace mal escrito no puede tumbar el layout entero.
    $view = $this->blade('<x-kore::sidebar.item label="User" route="users.show" href="/fallback" />');

    $view->assertSee('href="/fallback"', false);
});

it('opens external links safely', function () {
    $view = $this->blade('<x-kore::sidebar.item label="Docs" href="https://example.com" target="_blank" />');

    $view->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

// --- Ruta activa (resuelta en el SERVIDOR) ---

it('marks the item active on the current route', function () {
    $this->get('/dashboard');

    $view = $this->blade('<x-kore::sidebar.item label="Dashboard" route="dashboard" />');

    $view->assertSee('data-kore-active="true"', false)
        ->assertSee('aria-current="page"', false);
});

it('does not mark an item on a different route', function () {
    $this->get('/dashboard');

    $view = $this->blade('<x-kore::sidebar.item label="Users" route="users.index" />');

    $view->assertDontSee('data-kore-active="true"', false)
        ->assertDontSee('aria-current', false);
});

it('matches a wildcard pattern via match', function () {
    $this->get('/users/3');

    $view = $this->blade('<x-kore::sidebar.item label="Users" route="users.index" match="users.*" />');

    $view->assertSee('data-kore-active="true"', false);
});

it('lets an explicit active prop force the state', function () {
    $this->get('/dashboard');

    $forced = $this->blade('<x-kore::sidebar.item label="X" route="users.index" :active="true" />');
    $forced->assertSee('data-kore-active="true"', false);

    $suppressed = $this->blade('<x-kore::sidebar.item label="X" route="dashboard" :active="false" />');
    $suppressed->assertDontSee('data-kore-active="true"', false);
});

// --- Sub-items: lo más delicado del componente ---

it('renders a disclosure button instead of a link when it has children', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.item label="Ajustes" icon="settings">
            <x-kore::sidebar.item label="Perfil" route="settings.profile" />
        </x-kore::sidebar.item>
    BLADE);

    $view->assertSee('<button', false)
        ->assertSee('data-kore-has-children', false)
        ->assertSee('toggleItem($event)', false)
        ->assertSee('Perfil');
});

it('OPENS the parent in the server HTML when a child is on the current route', function () {
    // El test más importante del componente. Si esto se rompe, el sub-menú se pinta
    // cerrado y se abre de golpe al arrancar Alpine: exactamente el parpadeo que todo
    // este diseño existe para evitar.
    $this->get('/settings/profile');

    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.item label="Ajustes" icon="settings">
            <x-kore::sidebar.item label="Perfil" route="settings.profile" />
        </x-kore::sidebar.item>
    BLADE);

    $view->assertSee('data-kore-open="true"', false)
        ->assertSee('aria-expanded="true"', false);
});

it('keeps the parent closed when no child is active', function () {
    $this->get('/dashboard');

    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.item label="Ajustes" icon="settings">
            <x-kore::sidebar.item label="Perfil" route="settings.profile" />
        </x-kore::sidebar.item>
    BLADE);

    $view->assertSee('data-kore-open="false"', false)
        ->assertSee('aria-expanded="false"', false);
});

it('marks the parent active when a child is active', function () {
    $this->get('/settings/profile');

    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.item label="Ajustes">
            <x-kore::sidebar.item label="Perfil" route="settings.profile" />
        </x-kore::sidebar.item>
    BLADE);

    // El padre no apunta a la ruta actual, pero contiene al que sí.
    $view->assertSee('data-kore-active="true"', false);
});

it('propagates an active grandchild all the way up', function () {
    // La detección se apoya en el marcador del HTML, así que compone sola a
    // cualquier profundidad: el nieto marca, el hijo hereda y lo re-emite, el abuelo lo ve.
    $this->get('/settings/profile');

    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.item label="Abuelo">
            <x-kore::sidebar.item label="Padre">
                <x-kore::sidebar.item label="Nieto" route="settings.profile" />
            </x-kore::sidebar.item>
        </x-kore::sidebar.item>
    BLADE);

    expect(substr_count($view->__toString(), 'data-kore-open="true"'))->toBe(2);
});

it('honours the opened prop', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.item label="Ajustes" :opened="true">
            <x-kore::sidebar.item label="Perfil" href="/x" />
        </x-kore::sidebar.item>
    BLADE);

    $view->assertSee('data-kore-open="true"', false);
});

// --- Badges y estado ---

it('renders a text badge', function () {
    $view = $this->blade('<x-kore::sidebar.item label="Tareas" href="/t" badge="12" />');

    $view->assertSee('12');
});

it('renders a dot badge with an accessible label', function () {
    // El dot es el único badge que sobrevive al colapso, pero un punto de color no
    // dice nada a un lector de pantalla: el texto va en un sr-only.
    $view = $this->blade('<x-kore::sidebar.item label="Mensajes" href="/m" badge="3" badge-variant="dot" />');

    $view->assertSee('sr-only', false)
        ->assertSee('rounded-full', false);
});

it('disables an item', function () {
    $view = $this->blade('<x-kore::sidebar.item label="X" href="/x" :disabled="true" />');

    $view->assertSee('aria-disabled="true"', false)
        ->assertSee('tabindex="-1"', false);
});

// --- Herencia desde el sidebar ---

it('inherits wire:navigate from the parent sidebar', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar :navigate="true">
            <x-kore::sidebar.item label="Inicio" href="/home" />
        </x-kore::sidebar>
    BLADE);

    $view->assertSee('wire:navigate', false);
});

it('does not add wire:navigate by default', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar>
            <x-kore::sidebar.item label="Inicio" href="/home" />
        </x-kore::sidebar>
    BLADE);

    $view->assertDontSee('wire:navigate', false);
});
