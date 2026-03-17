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
