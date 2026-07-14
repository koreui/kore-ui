<?php

it('renders an accessible button', function () {
    $view = $this->blade('<x-kore::sidebar.toggle />');

    $view->assertSee('<button', false)
        ->assertSee('type="button"', false)
        ->assertSee('aria-label="Alternar navegación"', false)
        ->assertSee('aria-controls="main"', false);
});

it('drives the store, so it works anywhere on the page', function () {
    // El toggle no está anidado dentro del sidebar: habla con el store global. Por eso
    // puede vivir en la navbar, o suelto en cualquier parte de la página.
    $view = $this->blade('<x-kore::sidebar.toggle />');

    $view->assertSee('$store.koreSidebar.handleToggle', false);
});

it('targets a specific sidebar', function () {
    $view = $this->blade('<x-kore::sidebar.toggle for="tools" />');

    $view->assertSee("handleToggle('tools')", false)
        ->assertSee('aria-controls="tools"', false);
});

it('reports the right expanded state for the viewport it is in', function () {
    // En móvil el botón abre/cierra un drawer; en escritorio colapsa/expande. El
    // aria-expanded tiene que contar la historia correcta en cada caso, no una fija.
    $view = $this->blade('<x-kore::sidebar.toggle />');

    $view->assertSee('isMobile', false)
        ->assertSee('isOpen', false)
        ->assertSee('isCollapsed', false);
});

it('accepts a custom icon and label', function () {
    $view = $this->blade('<x-kore::sidebar.toggle icon="menu" label="Abrir menú" />');

    $view->assertSee('aria-label="Abrir menú"', false);
});
