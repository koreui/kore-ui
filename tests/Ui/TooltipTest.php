<?php

it('renders with text', function () {
    $view = $this->blade('<x-kore::tooltip text="Help text"><button>Hover</button></x-kore::tooltip>');
    $view->assertSee('Help text')
        ->assertSee('Hover');
});

/**
 * El panel ya NO lleva `role="tooltip"`.
 *
 * Va `aria-hidden`, porque el texto accesible lo da el `<span class="sr-only">`
 * del componente: con los dos, un lector lo encontraría dos veces. Un rol dentro
 * de un nodo que nadie lee no significa nada.
 */
it('mantiene el panel flotante y su texto', function () {
    $view = $this->blade('<x-kore::tooltip text="Info"><span>?</span></x-kore::tooltip>');

    $view->assertSee('x-teleport="body"', false)
        ->assertSee('data-kore-teleport', false)
        ->assertSee('Info');
});

it('renders with alpine data', function () {
    $view = $this->blade('<x-kore::tooltip text="Info"><span>?</span></x-kore::tooltip>');
    $view->assertSee('x-data=', false)
        ->assertSee('x-show="show"', false);
});

it('renders top position by default', function () {
    $view = $this->blade('<x-kore::tooltip text="Info"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'top'", false);
});

it('renders bottom position', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" position="bottom"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'bottom'", false);
});

it('renders left position', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" position="left"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'left'", false);
});

it('renders right position', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" position="right"><span>?</span></x-kore::tooltip>');
    $view->assertSee("placement: 'right'", false);
});

it('renders with custom delay', function () {
    $view = $this->blade('<x-kore::tooltip text="Info" :delay="500"><span>?</span></x-kore::tooltip>');
    $view->assertSee('500', false);
});

/**
 * El tooltip no existía para un lector de pantalla.
 *
 * El panel vive teleportado a `<body>` —lejos del control y sin `id`— y nadie
 * apuntaba a él. Medido en un navegador: `aria-describedby` a `null` en el
 * disparador, y el `<div role="tooltip">` colgando de `<body>` sin identificar.
 *
 * El texto accesible NO está en el panel: está en un `<span class="sr-only">`
 * del propio componente. Darle un id al nodo teleportado rompía la tabla del
 * DataTable —25 tooltips— porque el morph emparejaba el nodo del HTML nuevo con
 * el que ya colgaba de `<body>` y lo arrancaba de su ámbito de Alpine:
 * `ReferenceError: show is not defined`, medido en el censo de consola.
 */
it('pone el texto accesible junto al control, no en el panel teleportado', function () {
    $view = $this->blade('<x-kore::tooltip text="Ayuda"><button>Ir</button></x-kore::tooltip>');
    $html = $view->__toString();

    $view->assertSee('<span id="kore-tooltip-', false)
        ->assertSee("descripcionId: 'kore-tooltip-", false)
        ->assertSee('sr-only', false);

    // El `<span>` va ANTES del `<template x-teleport>`: es HTML del componente.
    expect(strpos($html, 'sr-only'))->toBeLessThan(strpos($html, 'x-teleport'));
});

/** Y el panel es decorativo, o el texto se leería dos veces. */
it('deja el panel flotante fuera del árbol de accesibilidad', function () {
    $view = $this->blade('<x-kore::tooltip text="Ayuda"><button>Ir</button></x-kore::tooltip>');

    $view->assertSee('aria-hidden="true"', false)
        ->assertDontSee('role="tooltip"', false);
});

/** Nada de ids en el nodo teleportado: es lo que rompía el morph. */
it('no le pone id al panel teleportado', function () {
    $view = $this->blade('<x-kore::tooltip text="Ayuda"><button>Ir</button></x-kore::tooltip>');
    $html = $view->__toString();

    $teleport = substr($html, strpos($html, 'x-teleport'));
    expect($teleport)->not->toContain('id="kore-tooltip-');
    expect($teleport)->not->toContain('x-bind:id');
});

/**
 * WCAG 1.4.13: lo que aparece al pasar por encima o al enfocar tiene que poder
 * descartarse sin mover el foco. Medido: `show` seguía en `true` tras Escape.
 *
 * La tecla se escucha en el elemento, no en `window`, y solo se marca si había
 * algo abierto — el contrato del Escape del resto de la librería.
 */
it('se cierra con Escape sin robarle la tecla a nadie', function () {
    $view = $this->blade('<x-kore::tooltip text="Ayuda"><button>Ir</button></x-kore::tooltip>');

    $view->assertSee('x-on:keydown.escape="if (show) { $event.preventDefault(); close() }"', false)
        ->assertDontSee('keydown.escape.window', false);
});
