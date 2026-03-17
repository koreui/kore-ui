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

    $view->assertDontSee('Light')
        ->assertDontSee('System')
        ->assertDontSee('Dark');
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
