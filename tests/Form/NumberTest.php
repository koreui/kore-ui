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
        ->assertSee('kore-invalid', false);
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

it('draws the border and focus ring on the group, not on the inner input', function () {
    // El anillo debe rodear el conjunto «− input +». Cuando lo pintaba el input
    // interno, `border-x-0` recortaba los lados y el botón «+» tapaba el ring:
    // el contorno salía a medias, de tres lados.
    $view = $this->blade('<x-kore::number label="Qty" name="qty" />');

    // `kore-control` es borde, sombra y halo de foco en una sola pieza; lleva
    // `:focus-within`, así que puesto en el grupo rodea el conjunto entero.
    $html = (string) $view;

    expect($html)->toContain('overflow-hidden kore-control')
        ->not->toContain('border-x-0');

    // Y el input de dentro va desnudo: sin borde ni campo propios.
    preg_match('/<input[^>]*class="([^"]*)"/', $html, $m);
    expect($m[1] ?? '')->toContain('border-0')->not->toContain('kore-control');
});

it('keeps the ring on the input itself when controls are hidden', function () {
    $view = $this->blade('<x-kore::number label="Qty" name="qty" :controls="false" />');

    $html = (string) $view;

    preg_match('/<input[^>]*class="([^"]*)"/', $html, $m);
    expect($m[1] ?? '')->toContain('kore-control');

    // Sin controles no hay grupo que pinte el marco.
    expect($html)->not->toContain('overflow-hidden kore-control');
});

it('paints the error border around the whole group', function () {
    $this->withViewErrors(['qty' => 'Fuera de rango']);

    $view = $this->blade('<x-kore::number label="Qty" name="qty" />');

    // El rojo va en el contenedor —junto a overflow-hidden— y no en el input.
    $view->assertSee('overflow-hidden kore-control kore-invalid', false);
});

it('draws the border on the group in currency mode too', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" />');

    $view->assertSee('overflow-hidden kore-control', false)
        ->assertDontSee('border-x-0', false);
});
