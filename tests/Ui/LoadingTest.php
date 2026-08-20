<?php

it('renders spinner by default', function () {
    $view = $this->blade('<x-kore::loading />');
    $view->assertSee('animate-spin', false);
});

it('renders dots type', function () {
    $view = $this->blade('<x-kore::loading type="dots" />');
    $view->assertSee('animate-bounce', false);
});

it('renders pulse type', function () {
    $view = $this->blade('<x-kore::loading type="pulse" />');
    $view->assertSee('animate-pulse', false);
});

it('renders with text', function () {
    $view = $this->blade('<x-kore::loading text="Loading..." />');
    $view->assertSee('Loading...');
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::loading size="sm" />');
    $view->assertSee('size-4', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::loading size="lg" />');
    $view->assertSee('size-8', false);
});

it('renders overlay mode', function () {
    $view = $this->blade('<x-kore::loading overlay />');
    $view->assertSee('absolute inset-0', false);
});

it('renders overlay with blur', function () {
    $view = $this->blade('<x-kore::loading overlay blur />');
    $view->assertSee('backdrop-blur-sm', false);
});

/**
 * Sin texto visible, la animación era la ÚNICA señal de que algo estaba
 * pasando. Medido: cero elementos con `role="status"` o `aria-live` en una
 * página con cuatro indicadores de carga.
 */
it('se anuncia como estado en curso', function () {
    $view = $this->blade('<x-kore::loading />');
    $view->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false)
        ->assertSee('Cargando');
});

it('no repite el texto cuando ya hay uno visible', function () {
    $view = $this->blade('<x-kore::loading text="Subiendo" />');
    $view->assertSee('Subiendo')
        ->assertDontSee('sr-only', false);
});

/**
 * El spinner se RALENTIZA con `prefers-reduced-motion`, no se apaga: es la única
 * señal de que algo pasa. Los puntos y el pulso sí se apagan del todo.
 */
it('marca sus animaciones según lo que aporte cada una', function () {
    $this->blade('<x-kore::loading type="spinner" />')->assertSee('kore-anim-spinner', false);
    $this->blade('<x-kore::loading type="dots" />')->assertSee('kore-anim-suave', false);
    $this->blade('<x-kore::loading type="pulse" />')->assertSee('kore-anim-suave', false);
});

/**
 * `announce` a false para quien ya anuncia su propio estado.
 *
 * El DataTable tiene su `aria-live` con el recuento de resultados: con los dos,
 * al filtrar un lector oía «Cargando» y a continuación «Mostrando 1 de 1».
 */
it('se puede callar cuando quien lo usa ya anuncia', function () {
    $view = $this->blade('<x-kore::loading :announce="false" />');
    $view->assertDontSee('role="status"', false)
        ->assertDontSee('aria-live', false)
        ->assertDontSee('Cargando');
});
