<?php

it('renders with image', function () {
    $view = $this->blade('<x-kore::avatar src="/avatar.jpg" name="John" />');
    $view->assertSee('src="/avatar.jpg"', false)
        ->assertSee('alt="John"', false);
});

it('renders initials from name', function () {
    $view = $this->blade('<x-kore::avatar name="John Doe" />');
    $view->assertSee('JD');
});

it('renders single initial for single name', function () {
    $view = $this->blade('<x-kore::avatar name="John" />');
    $view->assertSee('J');
});

it('renders fallback icon when no src or name', function () {
    $view = $this->blade('<x-kore::avatar />');
    $view->assertSee('<svg', false);
});

it('renders custom icon', function () {
    $view = $this->blade('<x-kore::avatar icon="bot" />');
    $view->assertSee('<svg', false);
});

it('renders circle shape by default', function () {
    $view = $this->blade('<x-kore::avatar name="Test" />');
    $view->assertSee('rounded-full', false);
});

it('renders square shape', function () {
    $view = $this->blade('<x-kore::avatar name="Test" shape="square" />');
    $view->assertSee('rounded-kore-md', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::avatar name="Test" size="sm" />');
    $view->assertSee('size-8', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::avatar name="Test" size="lg" />');
    $view->assertSee('size-12', false);
});

it('renders online presence', function () {
    $view = $this->blade('<x-kore::avatar name="Test" presence="online" />');
    $view->assertSee('bg-kore-success', false);
});

it('renders offline presence', function () {
    $view = $this->blade('<x-kore::avatar name="Test" presence="offline" />');
    $view->assertSee('bg-kore-muted-fg', false);
});

it('renders avatar group', function () {
    $view = $this->blade('
        <x-kore::avatar-group>
            <x-kore::avatar name="A B" />
            <x-kore::avatar name="C D" />
        </x-kore::avatar-group>
    ');
    $view->assertSee('AB')
        ->assertSee('CD')
        ->assertSee('-space-x-', false);
});

/**
 * Las iniciales pintan el color sobre su propio tinte al VEINTE por ciento, así
 * que es el mismo caso que las variantes `soft`. Medido antes: las cinco
 * combinaciones fallaban, de 2,67 a 3,52.
 */
it('usa los tokens de texto en las iniciales', function () {
    $view = $this->blade('<x-kore::avatar name="Ana Ruiz" />');
    expect($view->__toString())->toMatch('/text-kore-\w+-text/');
});

/**
 * El punto de presencia era color y nada más: sin texto ni `aria-label`, «en
 * línea» y «ocupado» se veían idénticos para quien no distingue el verde del
 * rojo.
 */
it('dice en palabras cuál es la presencia', function () {
    $this->blade('<x-kore::avatar name="A" presence="online" />')->assertSee('En línea');
    $this->blade('<x-kore::avatar name="A" presence="busy" />')->assertSee('Ocupado');
    $this->blade('<x-kore::avatar name="A" presence="away" />')->assertSee('Ausente');
    $this->blade('<x-kore::avatar name="A" presence="offline" />')->assertSee('Desconectado');
});

/** El pulso es decoración: se apaga entero con `prefers-reduced-motion`. */
it('marca el pulso de presencia', function () {
    $this->blade('<x-kore::avatar name="A" presence="online" :presence-pulse="true" />')
        ->assertSee('kore-anim-suave', false);
});
