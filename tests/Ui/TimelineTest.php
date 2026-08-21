<?php

it('renders timeline container', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Event" /></x-kore::timeline>');
    $view->assertSee('role="list"', false)
        ->assertSee('Event');
});

it('renders left variant by default', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Test" /></x-kore::timeline>');
    $view->assertSee('kore-timeline-left', false);
});

it('renders right variant', function () {
    $view = $this->blade('<x-kore::timeline variant="right"><x-kore::timeline.item label="Test" /></x-kore::timeline>');
    $view->assertSee('kore-timeline-right', false);
});

it('renders alternate variant', function () {
    $view = $this->blade('<x-kore::timeline variant="alternate"><x-kore::timeline.item label="Test" /></x-kore::timeline>');
    $view->assertSee('kore-timeline-alternate', false);
});

it('renders item with label', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Deployed" /></x-kore::timeline>');
    $view->assertSee('Deployed');
});

it('renders item with timestamp', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Event" timestamp="March 2024" /></x-kore::timeline>');
    $view->assertSee('March 2024');
});

it('renders item with icon', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Test" icon="check" /></x-kore::timeline>');
    $view->assertSee('<svg', false);
});

it('renders item with custom color', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Test" color="success" /></x-kore::timeline>');
    $view->assertSee('border-kore-success', false);
});

it('renders connector line', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Test" /></x-kore::timeline>');
    $view->assertSee('kore-timeline-connector', false);
});

it('renders item slot content', function () {
    $view = $this->blade('<x-kore::timeline><x-kore::timeline.item label="Event"><p>Details here</p></x-kore::timeline.item></x-kore::timeline>');
    $view->assertSee('Details here');
});

/**
 * `<time datetime>`: «12 ene» es ambiguo sin el valor en formato ISO al lado —
 * ni una máquina ni un lector pueden saber de qué año es.
 */
it('marca la fecha en formato legible por máquina cuando se le da', function () {
    $view = $this->blade('<x-kore::timeline.item label="Alta" timestamp="12 ene" datetime="2026-01-12">x</x-kore::timeline.item>');
    $view->assertSee('<time datetime="2026-01-12">12 ene</time>', false);
});

it('y se queda con el texto suelto cuando no se le da', function () {
    $view = $this->blade('<x-kore::timeline.item label="Alta" timestamp="hace un rato">x</x-kore::timeline.item>');
    $view->assertSee('hace un rato')
        ->assertDontSee('<time', false);
});
