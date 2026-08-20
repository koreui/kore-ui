<?php

it('renders true value with success color', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('text-kore-success', false);
});

it('renders false value with destructive color', function () {
    $view = $this->blade('<x-kore::boolean :value="false" />');
    $view->assertSee('text-kore-destructive', false);
});

it('renders check icon for true', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('<svg', false);
});

it('renders x icon for false', function () {
    $view = $this->blade('<x-kore::boolean :value="false" />');
    $view->assertSee('<svg', false);
});

it('renders custom true icon', function () {
    $view = $this->blade('<x-kore::boolean :value="true" trueIcon="check-circle" />');
    $view->assertSee('<svg', false);
});

it('renders custom colors', function () {
    $view = $this->blade('<x-kore::boolean :value="true" trueColor="primary" />');
    $view->assertSee('text-kore-primary', false);
});

it('renders sm size', function () {
    $view = $this->blade('<x-kore::boolean :value="true" size="sm" />');
    $view->assertSee('size-4', false);
});

it('renders lg size', function () {
    $view = $this->blade('<x-kore::boolean :value="true" size="lg" />');
    $view->assertSee('size-6', false);
});

it('has role img', function () {
    $view = $this->blade('<x-kore::boolean :value="true" />');
    $view->assertSee('role="img"', false);
});

/**
 * Decía literalmente «true» y «false»: en inglés, y sin significado para quien
 * lo oye. Un lector anunciaba «imagen, true» y nada más.
 */
it('se anuncia en el idioma de la interfaz', function () {
    $this->blade('<x-kore::boolean :value="true" />')->assertSee('aria-label="Sí"', false);
});

it('y lo mismo cuando es falso', function () {
    $this->blade('<x-kore::boolean :value="false" />')->assertSee('aria-label="No"', false);
});

it('admite etiquetas propias', function () {
    $this->blade('<x-kore::boolean :value="true" trueLabel="Activo" falseLabel="Inactivo" />')
        ->assertSee('aria-label="Activo"', false)
        ->assertDontSee('trueLabel', false);
});
