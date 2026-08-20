<?php

it('renders input variant by default', function () {
    $view = $this->blade('<x-kore::clipboard text="hello" />');
    $view->assertSee('KoreClipboard', false)
        ->assertSee('value="hello"', false);
});

it('renders with label', function () {
    $view = $this->blade('<x-kore::clipboard text="abc" label="API Key" />');
    $view->assertSee('API Key');
});

it('renders inline variant', function () {
    $view = $this->blade('<x-kore::clipboard text="code123" variant="inline" />');
    $view->assertSee('code123');
});

it('renders icon variant', function () {
    $view = $this->blade('<x-kore::clipboard text="secret" variant="icon" />');
    $view->assertSee('Copiar', false);
});

it('renders secret mode with password input', function () {
    $view = $this->blade('<x-kore::clipboard text="s3cret" :secret="true" />');
    $view->assertSee('type="password"', false);
});

it('renders secret inline with masked text', function () {
    $view = $this->blade('<x-kore::clipboard text="s3cret" variant="inline" :secret="true" />');
    $view->assertSee('••••••••');
});

it('renders copy button', function () {
    $view = $this->blade('<x-kore::clipboard text="copy me" />');
    $view->assertSee('copy()', false);
});

it('renders Alpine clipboard component', function () {
    $view = $this->blade('<x-kore::clipboard text="test" />');
    $view->assertSee('x-data="KoreClipboard', false);
});


/**
 * Los tres formatos llevan un botón que solo tiene un icono, y ninguno tenía
 * nombre: el de la variante `icon` se quedaba en un `title`, que no se expone de
 * forma fiable en táctil ni en todos los lectores. Medido: cuatro controles sin
 * nombre accesible en una página con las tres variantes.
 */
it('nombra el botón de copiar en las tres variantes', function () {
    foreach (['input', 'inline', 'icon'] as $variante) {
        $view = $this->blade('<x-kore::clipboard text="abc" variant="'.$variante.'" />');
        $view->assertSee('x-bind:aria-label="copied ?', false);
    }
});

/** El `title` no cuenta como nombre: ya no debe quedar ninguno. */
it('no se apoya en title', function () {
    $view = $this->blade('<x-kore::clipboard text="abc" variant="icon" />');
    $view->assertDontSee('title="', false);
});

/** El campo de la variante `input` es un control: sin nombre no dice de qué es. */
it('nombra el campo de la variante input', function () {
    $view = $this->blade('<x-kore::clipboard text="abc" label="Clave" />');
    $view->assertSee('<label for="kore-clipboard-', false);

    $sinEtiqueta = $this->blade('<x-kore::clipboard text="abc" />');
    $sinEtiqueta->assertSee('aria-label="Copiar"', false);
});

/** El cambio de icono es la única señal de que se copió, y es solo visual. */
it('anuncia el copiado', function () {
    $view = $this->blade('<x-kore::clipboard text="abc" />');
    $view->assertSee('aria-live="polite"', false)
        ->assertSee('role="status"', false);
});
