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
