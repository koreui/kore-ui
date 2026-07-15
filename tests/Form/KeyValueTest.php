<?php

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::key-value label="Metadatos" name="meta" />');

    $view->assertSee('Metadatos')
        ->assertSee('name="meta"', false);
});

it('renders KoreKeyValue Alpine data', function () {
    $view = $this->blade('<x-kore::key-value name="meta" />');

    $view->assertSee('KoreKeyValue', false);
});

it('wraps the editor in wire:ignore', function () {
    $view = $this->blade('<x-kore::key-value name="meta" />');

    $view->assertSee('wire:ignore', false);
});

it('renders hidden input for wire:model', function () {
    $view = $this->blade('<x-kore::key-value wire:model="meta" />');

    $view->assertSee('wire:model="meta"', false)
        ->assertSee('type="hidden"', false);
});

it('renders custom placeholders', function () {
    $view = $this->blade('<x-kore::key-value name="meta" key-placeholder="Header" value-placeholder="Contenido" />');

    $view->assertSee('Header')
        ->assertSee('Contenido');
});

it('renders the add button by default', function () {
    $view = $this->blade('<x-kore::key-value name="meta" />');

    $view->assertSee('addPair()', false);
});

it('hides the add button when not addable', function () {
    $view = $this->blade('<x-kore::key-value name="meta" :addable="false" />');

    $view->assertDontSee('addPair()', false);
});

it('renders remove buttons when deletable', function () {
    $view = $this->blade('<x-kore::key-value name="meta" />');

    $view->assertSee('removePair(index)', false);
});

it('renders the drag handle when reorderable', function () {
    $view = $this->blade('<x-kore::key-value name="meta" reorderable />');

    $view->assertSee('x-sort', false)
        ->assertSee('movePair', false);
});

it('passes max config', function () {
    $view = $this->blade('<x-kore::key-value name="meta" :max="5" />');

    $view->assertSee('&quot;max&quot;:5', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::key-value name="meta" disabled />');

    $view->assertSee('opacity-50', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['meta' => 'Metadatos inválidos.']);

    $view = $this->blade('<x-kore::key-value label="Metadatos" name="meta" />');

    $view->assertSee('Metadatos inválidos.')
        ->assertSee('role="alert"', false);
});
