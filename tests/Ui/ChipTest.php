<?php

it('renders with label', function () {
    $view = $this->blade('<x-kore::chip label="Tag" />');
    $view->assertSee('Tag');
});

it('renders soft variant by default', function () {
    $view = $this->blade('<x-kore::chip label="Test" color="primary" />');
    $view->assertSee('bg-kore-primary/10', false);
});

it('renders solid variant', function () {
    $view = $this->blade('<x-kore::chip label="Test" variant="solid" color="primary" />');
    $view->assertSee('bg-kore-primary', false);
});

it('renders outline variant', function () {
    $view = $this->blade('<x-kore::chip label="Test" variant="outline" color="primary" />');
    $view->assertSee('border-kore-primary', false);
});

it('renders with icon', function () {
    $view = $this->blade('<x-kore::chip label="Star" icon="star" />');
    $view->assertSee('<svg', false);
});

it('renders with image', function () {
    $view = $this->blade('<x-kore::chip label="User" image="/avatar.jpg" />');
    $view->assertSee('src="/avatar.jpg"', false)
        ->assertSee('rounded-full', false);
});

it('renders removable chip with close button', function () {
    $view = $this->blade('<x-kore::chip label="Remove me" :removable="true" />');
    $view->assertSee('x-data', false)
        ->assertSee('chip-removed', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::chip label="Sm" size="sm" />');
    $view->assertSee('text-xs', false)
        ->assertSee('px-2', false);
});

it('renders success color', function () {
    $view = $this->blade('<x-kore::chip label="Active" color="success" />');
    $view->assertSee('bg-kore-success/10', false);
});

it('renders rounded-full shape', function () {
    $view = $this->blade('<x-kore::chip label="Pill" />');
    $view->assertSee('rounded-full', false);
});

/** Mismo arreglo que en el badge: el color como texto usa el token `-text`. */
it('usa los tokens de texto en soft y outline', function () {
    foreach (['soft', 'outline'] as $variante) {
        foreach (['primary', 'success', 'info', 'destructive', 'warning'] as $color) {
            $this->blade('<x-kore::chip variant="'.$variante.'" color="'.$color.'" label="X" />')
                ->assertSee('text-kore-'.$color.'-text', false);
        }
    }
});

/**
 * El botón de quitar medía 18×18. WCAG 2.2 pide 24×24 como mínimo, y es el
 * mismo caso que el `rating` con sus estrellas de 20×20.
 */
it('da tamaño táctil al botón de quitar', function () {
    $view = $this->blade('<x-kore::chip label="X" removable />');
    $view->assertSee('size-6', false)
        ->assertDontSee('p-0.5', false);
});
