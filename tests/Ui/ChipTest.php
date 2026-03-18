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
