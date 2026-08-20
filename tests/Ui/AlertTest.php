<?php

it('renders with title', function () {
    $view = $this->blade('<x-kore::alert title="Success!" />');
    $view->assertSee('Success!');
});

it('renders with description', function () {
    $view = $this->blade('<x-kore::alert description="Something happened" />');
    $view->assertSee('Something happened');
});

it('renders with slot content', function () {
    $view = $this->blade('<x-kore::alert>Custom message</x-kore::alert>');
    $view->assertSee('Custom message');
});

it('renders info type icon by default', function () {
    $view = $this->blade('<x-kore::alert title="Info" />');
    $view->assertSee('<svg', false);
});

it('renders success type icon', function () {
    $view = $this->blade('<x-kore::alert type="success" title="Done" />');
    $view->assertSee('<svg', false);
});

it('renders warning type icon', function () {
    $view = $this->blade('<x-kore::alert type="warning" title="Warning" />');
    $view->assertSee('<svg', false);
});

it('renders destructive type icon', function () {
    $view = $this->blade('<x-kore::alert type="destructive" title="Error" />');
    $view->assertSee('<svg', false);
});

/**
 * `role="alert"` es una región ASSERTIVE: interrumpe al lector para leerla.
 *
 * Eso está bien para un aviso que aparece de pronto, y muy mal para uno que ya
 * estaba en la página al cargar. Medido en un navegador: doce alertas estáticas
 * en una página, las doce con el rol, todas anunciándose de golpe al abrirla.
 */
it('no interrumpe al lector si no es un aviso que aparece', function () {
    $view = $this->blade('<x-kore::alert title="Aviso" />');
    $view->assertDontSee('role="alert"', false)
        ->assertDontSee('role="status"', false);
});

it('sí lo hace cuando la alerta aparece y se va sola', function () {
    $view = $this->blade('<x-kore::alert title="Aviso" :timeout="5" />');
    $view->assertSee('role="alert"', false);
});

it('deja elegir el nivel de aviso', function () {
    $this->blade('<x-kore::alert title="A" live="polite" />')->assertSee('role="status"', false);
    $this->blade('<x-kore::alert title="A" live="assertive" />')->assertSee('role="alert"', false);
    $this->blade('<x-kore::alert title="A" :timeout="5" live="off" />')->assertDontSee('role=', false);
});

it('renders closeable button', function () {
    $view = $this->blade('<x-kore::alert title="Test" closeable />');
    $view->assertSee('x-on:click="show = false"', false);
});

it('renders soft variant by default', function () {
    $view = $this->blade('<x-kore::alert type="success" title="Done" />');
    $view->assertSee('bg-kore-success/10', false);
});

it('renders solid variant', function () {
    $view = $this->blade('<x-kore::alert type="success" variant="solid" title="Done" />');
    $view->assertSee('bg-kore-success', false)
        ->assertSee('text-kore-success-fg', false);
});

it('hides icon when show-icon is false', function () {
    $view = $this->blade('<x-kore::alert title="Test" :show-icon="false" />');
    $view->assertDontSee('size-5', false);
});


/**
 * La descripción llevaba `opacity-90`, y eso hunde el contraste de un texto que
 * en varias combinaciones ya iba justo. Medido: fallaba en once de las doce
 * combinaciones de variante y tipo.
 */
it('no baja la opacidad del texto secundario', function () {
    $view = $this->blade('<x-kore::alert title="T" description="D" />');
    $view->assertDontSee('opacity-90', false);
});

/**
 * El cierre medía 20 px de ancho. WCAG 2.2 pide 24×24 como mínimo.
 */
it('da tamaño táctil al cierre', function () {
    $view = $this->blade('<x-kore::alert title="T" closeable />');
    $view->assertSee('size-6', false)
        ->assertSee('aria-label="Cerrar"', false);
});

/** Las variantes que pintan el color como TEXTO usan el token `-text`. */
it('usa los tokens de texto en soft y outline', function () {
    foreach (['soft', 'outline'] as $variante) {
        foreach (['success', 'info', 'destructive'] as $tipo) {
            $this->blade('<x-kore::alert variant="'.$variante.'" type="'.$tipo.'" title="T" />')
                ->assertSee('text-kore-'.$tipo.'-text', false);
        }
    }
});
