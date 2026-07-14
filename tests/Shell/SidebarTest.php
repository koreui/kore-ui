<?php

use Illuminate\Support\Facades\Route;
use KoreUi\Shell\SidebarState;

beforeEach(function () {
    Route::get('/dashboard', fn () => '')->name('dashboard');
    Route::get('/users', fn () => '')->name('users.index');
    Route::get('/users/{user}', fn () => '')->name('users.show');
    Route::getRoutes()->refreshNameLookups();
});

afterEach(function () {
    unset($_COOKIE[SidebarState::COOKIE]);
});

// --- Contenedor ---

it('renders a nav with the sidebar landmark', function () {
    $view = $this->blade('<x-kore::sidebar>content</x-kore::sidebar>');

    $view->assertSee('<nav', false)
        ->assertSee('aria-label="Sidebar"', false)
        ->assertSee('kore-sidebar', false);
});

it('registers the Alpine component with its id', function () {
    $view = $this->blade('<x-kore::sidebar id="tools">x</x-kore::sidebar>');

    $view->assertSee('KoreSidebar(', false)
        ->assertSee('data-kore-sidebar-id="tools"', false);
});

it('emits the expanded state by default', function () {
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertSee('data-kore-sidebar="expanded"', false);
});

it('emits the collapsed state from the server when the cookie says so', function () {
    // El corazón del diseño: el estado sale ya resuelto del SERVIDOR, no de Alpine.
    // Por eso no hay salto de layout en la primera carga.
    $_COOKIE[SidebarState::COOKIE] = '{"main":1}';
    request()->cookies->set(SidebarState::COOKIE, '{"main":1}');

    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertSee('data-kore-sidebar="collapsed"', false);
});

it('lets the cookie win over the collapsed prop', function () {
    // La prop es solo el valor de la primera visita; la cookie es lo que el usuario eligió.
    $_COOKIE[SidebarState::COOKIE] = '{"main":0}';
    request()->cookies->set(SidebarState::COOKIE, '{"main":0}');

    $view = $this->blade('<x-kore::sidebar :collapsed="true">x</x-kore::sidebar>');

    $view->assertSee('data-kore-sidebar="expanded"', false);
});

it('ignores the cookie when persistence is off', function () {
    $_COOKIE[SidebarState::COOKIE] = '{"main":1}';
    request()->cookies->set(SidebarState::COOKIE, '{"main":1}');

    $view = $this->blade('<x-kore::sidebar :persist="false">x</x-kore::sidebar>');

    $view->assertSee('data-kore-sidebar="expanded"', false);
});

it('treats rail as collapsed with hover expansion', function () {
    $view = $this->blade('<x-kore::sidebar :rail="true">x</x-kore::sidebar>');

    $view->assertSee('data-kore-sidebar="collapsed"', false)
        ->assertSee('data-hover-expand="true"', false);
});

it('passes the widths as CSS custom properties, not Tailwind classes', function () {
    // El ancho tiene que ser una variable CSS para que el servidor pueda fijarlo y
    // la transición sea CSS puro.
    $view = $this->blade('<x-kore::sidebar width="20rem" collapsed-width="5rem">x</x-kore::sidebar>');

    $view->assertSee('--kore-sidebar-width: 20rem', false)
        ->assertSee('--kore-sidebar-width-collapsed: 5rem', false);
});

it('rejects a width that tries to smuggle CSS', function () {
    $view = $this->blade('<x-kore::sidebar width="16rem; background: url(evil)">x</x-kore::sidebar>');

    $view->assertDontSee('url(evil)', false)
        ->assertSee('--kore-sidebar-width: 16rem;', false);
});

it('renders the placement and breakpoint the CSS keys off', function () {
    $view = $this->blade('<x-kore::sidebar placement="right" breakpoint="md">x</x-kore::sidebar>');

    $view->assertSee('data-placement="right"', false)
        ->assertSee('data-breakpoint="md"', false);
});

it('falls back to a known breakpoint when given nonsense', function () {
    $view = $this->blade('<x-kore::sidebar breakpoint="enormous">x</x-kore::sidebar>');

    $view->assertSee('data-breakpoint="lg"', false);
});

it('renders header and footer slots', function () {
    $view = $this->blade(<<<'BLADE'
        <x-kore::sidebar>
            <x-slot:header>MI MARCA</x-slot:header>
            <x-slot:footer>SALIR</x-slot:footer>
            items
        </x-kore::sidebar>
    BLADE);

    $view->assertSee('MI MARCA')->assertSee('SALIR')->assertSee('items');
});

it('renders the mobile backdrop, as a sibling of the nav', function () {
    // Hermano y no hijo: en móvil el nav lleva un transform, y un hijo lo heredaría.
    $view = $this->blade('<x-kore::sidebar>x</x-kore::sidebar>');

    $view->assertSee('kore-sidebar-backdrop', false)
        ->assertSee('closeMobile', false);
});

it('omits the backdrop when overlay is off', function () {
    $view = $this->blade('<x-kore::sidebar :overlay="false">x</x-kore::sidebar>');

    $view->assertDontSee('kore-sidebar-backdrop', false);
});
