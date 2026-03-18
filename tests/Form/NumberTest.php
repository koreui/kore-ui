<?php

it('renders increment and decrement buttons', function () {
    $view = $this->blade('<x-kore::number label="Quantity" name="quantity" />');

    $view->assertSee('Quantity')
        ->assertSee('increment', false)
        ->assertSee('decrement', false);
});

it('hides controls when controls is false', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" :controls="false" />');

    $view->assertDontSee('lucide-minus', false)
        ->assertDontSee('lucide-plus', false);
});

it('renders min max step attributes', function () {
    $view = $this->blade('<x-kore::number label="Qty" name="qty" :min="1" :max="100" :step="5" />');

    $view->assertSee('min="1"', false)
        ->assertSee('max="100"', false)
        ->assertSee('step="5"', false);
});

it('renders type number', function () {
    $view = $this->blade('<x-kore::number label="Amount" name="amount" />');

    $view->assertSee('type="number"', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::number label="Qty" name="qty" disabled />');

    $view->assertSee('disabled', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['qty' => 'Must be positive']);

    $view = $this->blade('<x-kore::number label="Qty" name="qty" />');

    $view->assertSee('Must be positive')
        ->assertSee('border-kore-destructive', false);
});

it('renders type number for decimal mode (default)', function () {
    $view = $this->blade('<x-kore::number label="Amount" name="amount" />');

    $view->assertSee('type="number"', false);
});

it('renders type text for currency mode', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" />');

    $view->assertSee('type="text"', false);
});

it('renders KoreNumber Alpine data in currency mode', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" />');

    $view->assertSee('KoreNumber', false);
});

it('does not render KoreNumber in decimal mode', function () {
    $view = $this->blade('<x-kore::number label="Amount" name="amount" />');

    $view->assertDontSee('KoreNumber', false);
});

it('renders hidden input for wire:model in currency mode', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" />');

    $view->assertSee('x-ref="hiddenInput"', false);
});

it('passes currency config to JS', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" currency="MXN" />');

    $view->assertSee('&quot;currency&quot;:&quot;MXN&quot;', false);
});

it('renders controls in currency mode', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" />');

    $view->assertSee('increment()', false);
});

it('hides controls when controls false in currency mode', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" :controls="false" />');

    $view->assertDontSee('lucide-minus', false)
        ->assertDontSee('lucide-plus', false);
});
