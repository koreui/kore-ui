<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/dashboard', fn () => '')->name('dashboard');
    Route::get('/users', fn () => '')->name('users.index');
    Route::getRoutes()->refreshNameLookups();
});

it('renders a section header with its label', function () {
    $view = $this->blade('<x-kore::sidebar.group label="Gestión">items</x-kore::sidebar.group>');

    $view->assertSee('Gestión')
        ->assertSee('uppercase', false)
        ->assertSee('items');
});

it('is not a link: a group groups, it does not navigate', function () {
    $view = $this->blade('<x-kore::sidebar.group label="Gestión">items</x-kore::sidebar.group>');

    $view->assertDontSee('<a ', false)
        ->assertDontSee('aria-current', false);
});

it('renders a line separator by default and can drop it', function () {
    $withLine = $this->blade('<x-kore::sidebar.group label="A">x</x-kore::sidebar.group>');
    $withLine->assertSee('border-t', false);

    $withoutLine = $this->blade('<x-kore::sidebar.group label="A" separator="none">x</x-kore::sidebar.group>');
    $withoutLine->assertDontSee('border-t', false);
});

it('keeps the label in a fading span so items survive the collapse', function () {
    // Al colapsar, el título del grupo se desvanece pero sus items siguen ahí como
    // iconos. Por eso el label lleva la clase que el CSS anima, y el grupo no se oculta.
    $view = $this->blade('<x-kore::sidebar.group label="Gestión">x</x-kore::sidebar.group>');

    $view->assertSee('kore-sidebar-label', false);
});

it('renders a collapsible group as a disclosure', function () {
    $view = $this->blade('<x-kore::sidebar.group label="Reportes" :collapsible="true">x</x-kore::sidebar.group>');

    $view->assertSee('<button', false)
        ->assertSee('data-kore-has-children', false)
        ->assertSee('aria-expanded', false)
        ->assertSee('aria-controls', false);
});

it('opens a collapsed group from the server when it holds the active route', function () {
    $this->get('/users');

    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.group label="Gestión" :collapsible="true" :collapsed="true">
            <x-kore::sidebar.item label="Usuarios" route="users.index" />
        </x-kore::sidebar.group>
    BLADE);

    $view->assertSee('data-kore-open="true"', false);
});

it('stays closed when it does not hold the active route', function () {
    $this->get('/dashboard');

    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar.group label="Gestión" :collapsible="true" :collapsed="true">
            <x-kore::sidebar.item label="Usuarios" route="users.index" />
        </x-kore::sidebar.group>
    BLADE);

    $view->assertSee('data-kore-open="false"', false);
});

it('renders a custom header slot', function () {
    $view = $this->blade('<x-kore::sidebar.group header="MI CABECERA">x</x-kore::sidebar.group>');

    $view->assertSee('MI CABECERA');
});

it('animates the section title height instead of dropping it', function () {
    // Ocultar solo el texto no basta: la fila entera tiene que perder su ALTURA, o al
    // colapsar desaparecería de golpe y todo lo de abajo pegaría un salto hacia arriba.
    // Es el mismo truco de grid-template-rows que usan los sub-menús.
    $view = $this->blade('<x-kore::sidebar.group label="Gestión">x</x-kore::sidebar.group>');

    $view->assertSee('kore-sidebar-group-header', false);
});
