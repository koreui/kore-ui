<?php

it('renders the container', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel>A</x-kore::splitter.panel><x-kore::splitter.panel>B</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('KoreSplitter', false);
});

it('renders horizontal by default', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('flex-row', false);
});

it('renders vertical orientation', function () {
    $view = $this->blade('<x-kore::splitter orientation="vertical"><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('flex-col', false);
});

it('renders panel content', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel>Panel A</x-kore::splitter.panel><x-kore::splitter.panel>Panel B</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('Panel A')
        ->assertSee('Panel B');
});

it('renders panel with data attributes', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel :size="30" :minSize="10">A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('data-panel-size="30"', false)
        ->assertSee('data-panel-min="10"', false);
});

it('passes gutter size config', function () {
    $view = $this->blade('<x-kore::splitter :gutterSize="12"><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('gutterSize: 12', false);
});

it('renders role group', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('role="group"', false);
});

it('passes state key', function () {
    $view = $this->blade('<x-kore::splitter stateKey="my-layout"><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee("stateKey: 'my-layout'", false);
});

it('renders overflow auto on panels', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('overflow-auto', false);
});

it('renders data-splitter-panel attribute', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('data-splitter-panel', false);
});

it('renders panel max size', function () {
    $view = $this->blade('<x-kore::splitter><x-kore::splitter.panel :maxSize="80">A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('data-panel-max="80"', false);
});

it('renders with custom attributes', function () {
    $view = $this->blade('<x-kore::splitter class="h-96"><x-kore::splitter.panel>A</x-kore::splitter.panel></x-kore::splitter>');
    $view->assertSee('h-96', false);
});
