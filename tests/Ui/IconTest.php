<?php

// Rendering
it('renders an SVG icon', function () {
    $view = $this->blade('<x-kore::icon name="search" />');
    $view->assertSee('<svg', false);
});

it('renders with default md size', function () {
    $view = $this->blade('<x-kore::icon name="search" />');
    $view->assertSee('size-4', false);
});

// Sizes
it('renders xs size', function () {
    $view = $this->blade('<x-kore::icon name="search" size="xs" />');
    $view->assertSee('size-3', false);
});

it('renders sm size', function () {
    $view = $this->blade('<x-kore::icon name="search" size="sm" />');
    $view->assertSee('size-3.5', false);
});

it('renders lg size', function () {
    $view = $this->blade('<x-kore::icon name="search" size="lg" />');
    $view->assertSee('size-5', false);
});

it('renders xl size', function () {
    $view = $this->blade('<x-kore::icon name="search" size="xl" />');
    $view->assertSee('size-6', false);
});

it('renders 2xl size', function () {
    $view = $this->blade('<x-kore::icon name="search" size="2xl" />');
    $view->assertSee('size-8', false);
});

// Colors
it('renders with no color class by default', function () {
    $view = $this->blade('<x-kore::icon name="search" />');
    $view->assertDontSee('text-kore-', false);
});

it('renders primary color', function () {
    $view = $this->blade('<x-kore::icon name="search" color="primary" />');
    $view->assertSee('text-kore-primary', false);
});

it('renders success color', function () {
    $view = $this->blade('<x-kore::icon name="check-circle" color="success" />');
    $view->assertSee('text-kore-success', false);
});

it('renders destructive color', function () {
    $view = $this->blade('<x-kore::icon name="x-circle" color="destructive" />');
    $view->assertSee('text-kore-destructive', false);
});

it('renders warning color', function () {
    $view = $this->blade('<x-kore::icon name="alert-triangle" color="warning" />');
    $view->assertSee('text-kore-warning', false);
});

it('renders info color', function () {
    $view = $this->blade('<x-kore::icon name="info" color="info" />');
    $view->assertSee('text-kore-info', false);
});

it('renders muted color', function () {
    $view = $this->blade('<x-kore::icon name="search" color="muted" />');
    $view->assertSee('text-kore-muted-fg', false);
});

it('renders secondary color', function () {
    $view = $this->blade('<x-kore::icon name="search" color="secondary" />');
    $view->assertSee('text-kore-secondary-fg', false);
});

// Error state
it('renders error state with destructive color', function () {
    $view = $this->blade('<x-kore::icon name="alert-circle" error />');
    $view->assertSee('text-kore-destructive', false);
});

it('error overrides color prop', function () {
    $view = $this->blade('<x-kore::icon name="alert-circle" color="success" error />');
    $view->assertSee('text-kore-destructive', false);
});

// Animation
it('renders spin animation', function () {
    $view = $this->blade('<x-kore::icon name="loader-2" animation="spin" />');
    $view->assertSee('animate-spin', false);
});

it('renders pulse animation', function () {
    $view = $this->blade('<x-kore::icon name="bell" animation="pulse" />');
    $view->assertSee('animate-pulse', false);
});

it('renders bounce animation', function () {
    $view = $this->blade('<x-kore::icon name="bell" animation="bounce" />');
    $view->assertSee('animate-bounce', false);
});

it('renders no animation class by default', function () {
    $view = $this->blade('<x-kore::icon name="search" />');
    $view->assertDontSee('animate-', false);
});

// Label
it('renders with right label by default', function () {
    $view = $this->blade('<x-kore::icon name="save" label="Guardar" />');
    $view->assertSeeInOrder(['<svg', 'Guardar'], false);
});

it('renders with left label', function () {
    $view = $this->blade('<x-kore::icon name="arrow-left" label="Regresar" labelPosition="left" />');
    $view->assertSeeInOrder(['Regresar', '<svg'], false);
});

it('wraps icon and label in inline-flex span', function () {
    $view = $this->blade('<x-kore::icon name="save" label="Guardar" />');
    $view->assertSee('inline-flex items-center gap-1.5', false);
});

it('renders without wrapper when no label', function () {
    $view = $this->blade('<x-kore::icon name="search" />');
    $view->assertDontSee('inline-flex items-center gap-1.5', false);
});

// Accessibility
it('adds aria-hidden by default', function () {
    $view = $this->blade('<x-kore::icon name="search" />');
    $view->assertSee('aria-hidden="true"', false);
});

it('does not add aria-hidden when aria-label is provided', function () {
    $view = $this->blade('<x-kore::icon name="triangle-alert" aria-label="Advertencia" />');
    $view->assertSee('aria-label="Advertencia"', false)
        ->assertDontSee('aria-hidden', false);
});

// Stroke width
it('passes stroke-width attribute when provided', function () {
    $view = $this->blade('<x-kore::icon name="heart" :strokeWidth="1.5" />');
    $view->assertSee('stroke-width="1.5"', false);
});

// Attributes passthrough
it('passes through extra attributes', function () {
    $view = $this->blade('<x-kore::icon name="search" id="my-icon" data-test="foo" />');
    $view->assertSee('id="my-icon"', false)
        ->assertSee('data-test="foo"', false);
});

it('merges custom classes', function () {
    $view = $this->blade('<x-kore::icon name="search" class="opacity-50" />');
    $view->assertSee('opacity-50', false)
        ->assertSee('size-4', false);
});
