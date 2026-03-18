<?php

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::range label="Volume" name="volume" />');

    $view->assertSee('Volume')
        ->assertSee('name="volume"', false);
});

it('renders single range input by default', function () {
    $view = $this->blade('<x-kore::range name="volume" />');

    $view->assertSee('type="range"', false);
});

it('renders range mode with two inputs', function () {
    $view = $this->blade('<x-kore::range name="price" range />');

    // Range mode has hidden input + two range inputs
    $view->assertSee('x-model.number="low"', false)
        ->assertSee('x-model.number="high"', false);
});

it('renders min and max attributes', function () {
    $view = $this->blade('<x-kore::range name="temp" :min="0" :max="40" />');

    $view->assertSee('min="0"', false)
        ->assertSee('max="40"', false);
});

it('renders step attribute', function () {
    $view = $this->blade('<x-kore::range name="temp" :step="0.5" />');

    $view->assertSee('step="0.5"', false);
});

it('renders show value display', function () {
    $view = $this->blade('<x-kore::range name="volume" show-value />');

    $view->assertSee('x-text="value"', false);
});

it('renders show labels', function () {
    $view = $this->blade('<x-kore::range name="volume" show-labels :min="0" :max="100" />');

    $view->assertSee('0')
        ->assertSee('100');
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::range name="volume" size="sm" />');

    $view->assertSee('h-1', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::range name="volume" size="lg" />');

    $view->assertSee('h-3', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::range name="volume" disabled />');

    $view->assertSee('opacity-50', false);
});

it('forwards wire:model in single mode', function () {
    $view = $this->blade('<x-kore::range wire:model="volume" />');

    $view->assertSee('wire:model="volume"', false);
});

it('forwards wire:model on hidden input in range mode', function () {
    $view = $this->blade('<x-kore::range wire:model="priceRange" range />');

    $view->assertSee('wire:model="priceRange"', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['volume' => 'Volume is required.']);

    $view = $this->blade('<x-kore::range label="Volume" name="volume" />');

    $view->assertSee('Volume is required.')
        ->assertSee('role="alert"', false);
});

it('renders required indicator', function () {
    $view = $this->blade('<x-kore::range label="Volume" name="volume" required />');

    $view->assertSee('*');
});

it('renders KoreRange Alpine data', function () {
    $view = $this->blade('<x-kore::range name="volume" />');

    $view->assertSee('KoreRange', false);
});
