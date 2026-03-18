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
