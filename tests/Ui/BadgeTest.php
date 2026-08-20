<?php

it('renders with label', function () {
    $view = $this->blade('<x-kore::badge label="New" />');
    $view->assertSee('New');
});

it('renders with slot content', function () {
    $view = $this->blade('<x-kore::badge>Status</x-kore::badge>');
    $view->assertSee('Status');
});

it('renders soft variant by default', function () {
    $view = $this->blade('<x-kore::badge label="Test" />');
    $view->assertSee('bg-kore-primary/10', false);
});

it('renders solid variant', function () {
    $view = $this->blade('<x-kore::badge label="Test" variant="solid" />');
    $view->assertSee('bg-kore-primary', false);
});

it('renders outline variant', function () {
    $view = $this->blade('<x-kore::badge label="Test" variant="outline" />');
    $view->assertSee('border-kore-primary', false);
});

it('renders success color', function () {
    $view = $this->blade('<x-kore::badge label="Active" color="success" />');
    $view->assertSee('bg-kore-success/10', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::badge label="Sm" size="sm" />');
    $view->assertSee('px-1.5', false);
});

it('renders with icon', function () {
    $view = $this->blade('<x-kore::badge label="Star" icon="star" />');
    $view->assertSee('<svg', false);
});

it('renders dot variant', function () {
    $view = $this->blade('<x-kore::badge :dot="true" color="success" />');
    $view->assertSee('rounded-full', false)
        ->assertSee('bg-kore-success', false);
});

it('renders rounded-full shape', function () {
    $view = $this->blade('<x-kore::badge label="Pill" />');
    $view->assertSee('rounded-full', false);
});

/**
 * Las variantes que pintan el color COMO TEXTO usan el token `-text`.
 *
 * El color base está pensado para ser un FONDO: sobre su propio tinte al diez
 * por ciento se queda muy por debajo de AA. Medido en un navegador antes del
 * arreglo: success 3,01 · info 3,24 · destructive 3,91 · primary 4,08, con doce
 * de veintiuna combinaciones por debajo de 4,5.
 */
it('usa los tokens de texto en soft y outline', function () {
    foreach (['soft', 'outline'] as $variante) {
        foreach (['primary', 'success', 'info', 'destructive', 'warning'] as $color) {
            $this->blade('<x-kore::badge variant="'.$variante.'" color="'.$color.'" label="X" />')
                ->assertSee('text-kore-'.$color.'-text', false);
        }
    }
});
