<?php

use KoreUi\Shell\ShellContext;
use KoreUi\Shell\SidebarState;

afterEach(function () {
    unset($_COOKIE[SidebarState::COOKIE]);
});

it('renders the shell with a main region', function () {
    $view = $this->blade('<x-kore::shell>contenido</x-kore::shell>');

    $view->assertSee('data-kore-shell', false)
        ->assertSee('kore-shell-main', false)
        ->assertSee('<main', false)
        ->assertSee('contenido');
});

it('reserves no space when there is no sidebar', function () {
    $view = $this->blade('<x-kore::shell>x</x-kore::shell>');

    $view->assertDontSee('data-sidebar-left', false)
        ->assertDontSee('data-sidebar-right', false);
});

it('learns from the sidebar in its slot how much space to reserve', function () {
    // El mecanismo central del shell: el sidebar se registra al renderizarse (Blade
    // evalúa los slots antes que la plantilla que los contiene), así que el shell sabe
    // qué tiene dentro sin inspeccionar el HTML.
    $view = $this->blade(<<<'BLADE'
        <x-kore::shell>
            <x-slot:sidebar>
                <x-kore::sidebar>items</x-kore::sidebar>
            </x-slot:sidebar>
            contenido
        </x-kore::shell>
    BLADE);

    $view->assertSee('data-sidebar-left="expanded"', false);
});

it('reserves the collapsed width when the cookie says the sidebar is collapsed', function () {
    $_COOKIE[SidebarState::COOKIE] = '{"main":1}';
    request()->cookies->set(SidebarState::COOKIE, '{"main":1}');

    $view = $this->blade(<<<'BLADE'
        <x-kore::shell>
            <x-slot:sidebar><x-kore::sidebar>items</x-kore::sidebar></x-slot:sidebar>
            x
        </x-kore::shell>
    BLADE);

    $view->assertSee('data-sidebar-left="collapsed"', false);
});

it('reserves only the rail width, so the content never moves on hover', function () {
    // Rail es un estado propio y no "collapsed": el sidebar se ensancha al pasar el
    // cursor, pero el contenido tiene que quedarse quieto. Es el motivo de que haya
    // dos ejes de anchura en vez de uno.
    $view = $this->blade(<<<'BLADE'
        <x-kore::shell>
            <x-slot:sidebar><x-kore::sidebar :rail="true">items</x-kore::sidebar></x-slot:sidebar>
            x
        </x-kore::shell>
    BLADE);

    $view->assertSee('data-sidebar-left="rail"', false);
});

it('supports a second sidebar on the right', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::shell>
            <x-slot:sidebar><x-kore::sidebar id="main">nav</x-kore::sidebar></x-slot:sidebar>
            <x-slot:aside><x-kore::sidebar id="tools" placement="right">tools</x-kore::sidebar></x-slot:aside>
            x
        </x-kore::shell>
    BLADE);

    $view->assertSee('data-sidebar-left="expanded"', false)
        ->assertSee('data-sidebar-right="expanded"', false);
});

it('carries the breakpoint the drawer media queries key off', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::shell>
            <x-slot:sidebar><x-kore::sidebar breakpoint="md">x</x-kore::sidebar></x-slot:sidebar>
            x
        </x-kore::shell>
    BLADE);

    $view->assertSee('data-breakpoint="md"', false);
});

it('renders the navbar slot inside the main column, not over the sidebar', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::shell>
            <x-slot:navbar><x-kore::navbar>BARRA</x-kore::navbar></x-slot:navbar>
            contenido
        </x-kore::shell>
    BLADE);

    $html = $view->__toString();

    expect(strpos($html, 'kore-shell-main'))->toBeLessThan(strpos($html, 'BARRA'));
});

it('empties the registry after consuming it', function () {
    // consume() limpia al leer: si no, dos shells seguidos (o dos $this->blade() en el
    // mismo test) heredarían los sidebars del anterior.
    $context = app(ShellContext::class);
    $context->register(['id' => 'main', 'placement' => 'left']);

    expect($context->consume())->toHaveCount(1)
        ->and($context->consume())->toBe([]);
});

/**
 * Sin enlace de salto, quien navega con teclado tenía que pasar por todo el
 * menú —seis pulsaciones con un sidebar de tres niveles— antes de llegar al
 * contenido, y en cada página. Y `<main>` no tenía ni `id` al que saltar.
 */
it('ofrece saltar al contenido', function () {
    $view = $this->blade('<x-kore::shell>contenido</x-kore::shell>');
    $html = $view->__toString();

    $view->assertSee('href="#kore-contenido"', false)
        ->assertSee('Saltar al contenido')
        ->assertSee('id="kore-contenido"', false)
        // Solo visible al enfocarlo.
        ->assertSee('sr-only focus:not-sr-only', false);

    // Y es lo PRIMERO del documento: un salto que va después del menú no sirve.
    expect(strpos($html, 'Saltar al contenido'))->toBeLessThan(strpos($html, '<main'));
});

it('deja quitar el enlace de salto y cambiar su destino', function () {
    $this->blade('<x-kore::shell :skip-link="false">x</x-kore::shell>')
        ->assertDontSee('Saltar al contenido');

    $this->blade('<x-kore::shell main-id="principal" skip-label="Ir al contenido">x</x-kore::shell>')
        ->assertSee('href="#principal"', false)
        ->assertSee('Ir al contenido');
});
