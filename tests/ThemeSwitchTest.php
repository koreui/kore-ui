<?php

it('renders segmented variant by default', function () {
    $view = $this->blade('<x-kore::theme-switch />');

    $view->assertSee('role="radiogroup"', false)
        ->assertSee('aria-label="Theme selector"', false);
});

it('renders three buttons in segmented variant', function () {
    $view = $this->blade('<x-kore::theme-switch />');

    $view->assertSee('role="radio"', false)
        ->assertSee("setMode('light')", false)
        ->assertSee("setMode('system')", false)
        ->assertSee("setMode('dark')", false);
});

it('renders toggle variant', function () {
    $view = $this->blade('<x-kore::theme-switch variant="toggle" />');

    $view->assertSee('role="switch"', false)
        ->assertSee('aria-label="Toggle dark mode"', false);
});

it('renders dropdown variant', function () {
    $view = $this->blade('<x-kore::theme-switch variant="dropdown" />');

    $view->assertSee('aria-haspopup="true"', false)
        ->assertSee('role="menu"', false)
        ->assertSee('role="menuitem"', false);
});

it('applies small size classes', function () {
    $view = $this->blade('<x-kore::theme-switch size="sm" />');

    $view->assertSee('size-4', false)
        ->assertSee('text-xs', false);
});

it('applies large size classes', function () {
    $view = $this->blade('<x-kore::theme-switch size="lg" />');

    $view->assertSee('size-6', false)
        ->assertSee('text-base', false);
});

it('shows labels when prop is true', function () {
    $view = $this->blade('<x-kore::theme-switch :labels="true" />');

    $view->assertSee('Light')
        ->assertSee('System')
        ->assertSee('Dark');
});

it('hides labels by default', function () {
    $view = $this->blade('<x-kore::theme-switch />');

    // Sin `labels` no hay texto VISIBLE, pero el nombre accesible sigue estando:
    // los botones solo llevan un icono, y sin `aria-label` un lector de pantalla
    // los anuncia como «botón de radio» y nada más.
    $view->assertDontSee('<span>Light</span>', false)
        ->assertDontSee('<span>System</span>', false)
        ->assertDontSee('<span>Dark</span>', false);
});

it('names the icon-only buttons even without visible labels', function () {
    $view = $this->blade('<x-kore::theme-switch />');

    $view->assertSee('aria-label="Light"', false)
        ->assertSee('aria-label="System"', false)
        ->assertSee('aria-label="Dark"', false);
});

it('uses the custom labels as accessible names too', function () {
    // WCAG 2.5.3: el nombre accesible tiene que coincidir con la etiqueta
    // visible cuando la hay.
    $view = $this->blade('<x-kore::theme-switch :labels="true" light-label="Claro" dark-label="Oscuro" system-label="Auto" />');

    $view->assertSee('aria-label="Claro"', false)
        ->assertSee('aria-label="Oscuro"', false)
        ->assertSee('aria-label="Auto"', false);
});

it('accepts custom labels', function () {
    $view = $this->blade('<x-kore::theme-switch :labels="true" light-label="Claro" dark-label="Oscuro" system-label="Auto" />');

    $view->assertSee('Claro')
        ->assertSee('Oscuro')
        ->assertSee('Auto');
});

it('renders svg icons', function () {
    $view = $this->blade('<x-kore::theme-switch />');

    // Sun icon has a circle element, moon has unique path, monitor has rect
    $view->assertSee('<svg', false)
        ->assertSee('viewBox="0 0 24 24"', false);
});

it('toggle variant applies correct track sizes for sm', function () {
    $view = $this->blade('<x-kore::theme-switch variant="toggle" size="sm" />');

    $view->assertSee('h-5', false)
        ->assertSee('w-9', false);
});

it('renders @koreThemeScript directive', function () {
    $view = $this->blade('@koreThemeScript');

    $view->assertSee('<script>', false)
        ->assertSee('localStorage.getItem("kore-theme")', false)
        ->assertSee('classList.add("dark")', false)
        ->assertSee('</script>', false);
});

it('propagates attributes on segmented variant', function () {
    $view = $this->blade('<x-kore::theme-switch class="my-custom-class" />');

    $view->assertSee('my-custom-class', false);
});

// --- Regresión: Alpine necesita un x-data ancestro ---

it('declares its own x-data in every variant', function (string $variant) {
    // Alpine SOLO evalúa directivas dentro de un árbol que tenga x-data. Sin él, las
    // variantes `toggle` y `segmented` dependían de que la app hubiera puesto un x-data
    // en algún ancestro por casualidad (el layout de la demo lo hacía). En una página que
    // no lo tuviera, los clicks no hacían absolutamente nada, y sin ningún error.
    $html = $this->blade('<x-kore::theme-switch variant="'.$variant.'" />')->__toString();

    expect($html)->toContain('x-data');
})->with(['toggle', 'dropdown', 'segmented']);
