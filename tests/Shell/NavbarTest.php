<?php

it('renders a header landmark', function () {
    $view = $this->blade('<x-kore::navbar>contenido</x-kore::navbar>');

    $view->assertSee('<header', false)
        ->assertSee('kore-navbar', false)
        ->assertSee('contenido');
});

it('does NOT claim the toolbar role', function () {
    // role="toolbar" le promete al lector de pantalla un widget que se recorre con las
    // flechas. Una cabecera no lo es, y anunciarlo así rompe la navegación.
    $view = $this->blade('<x-kore::navbar>x</x-kore::navbar>');

    $view->assertDontSee('role="toolbar"', false);
});

it('includes the sidebar toggle by default', function () {
    $view = $this->blade('<x-kore::navbar>x</x-kore::navbar>');

    $view->assertSee('handleToggle', false);
});

it('can omit the toggle', function () {
    $view = $this->blade('<x-kore::navbar :toggle="false">x</x-kore::navbar>');

    $view->assertDontSee('handleToggle', false);
});

it('points the toggle at another sidebar', function () {
    $view = $this->blade('<x-kore::navbar toggle-for="tools">x</x-kore::navbar>');

    $view->assertSee("handleToggle('tools')", false);
});

it('is sticky by default and can be released', function () {
    $this->blade('<x-kore::navbar>x</x-kore::navbar>')
        ->assertSee('data-sticky="true"', false);

    $this->blade('<x-kore::navbar :sticky="false">x</x-kore::navbar>')
        ->assertSee('data-sticky="false"', false);
});

it('renders the start and end zones', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::navbar>
            <x-slot:start>IZQUIERDA</x-slot:start>
            <x-slot:end>DERECHA</x-slot:end>
            CENTRO
        </x-kore::navbar>
    BLADE);

    $view->assertSee('IZQUIERDA')->assertSee('CENTRO')->assertSee('DERECHA');
});

// --- El toolbar sigue comportándose como antes (retrocompatibilidad) ---

it('keeps role=toolbar on the toolbar itself', function () {
    $this->blade('<x-kore::toolbar>x</x-kore::toolbar>')
        ->assertSee('role="toolbar"', false);
});

it('lets the toolbar drop its role with false, since null falls back to the default', function () {
    $this->blade('<x-kore::toolbar :role="false">x</x-kore::toolbar>')
        ->assertDontSee('role=', false);
});
