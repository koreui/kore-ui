<?php

it('renders rect shape by default', function () {
    $view = $this->blade('<x-kore::skeleton />');
    $view->assertSee('rounded-kore-md', false)
        ->assertSee('aria-hidden="true"', false);
});

it('renders circle shape', function () {
    $view = $this->blade('<x-kore::skeleton shape="circle" />');
    $view->assertSee('rounded-full', false);
});

it('renders text shape', function () {
    $view = $this->blade('<x-kore::skeleton shape="text" />');
    $view->assertSee('rounded-kore-sm', false);
});

it('renders multiple text lines', function () {
    $view = $this->blade('<x-kore::skeleton shape="text" :lines="3" />');
    $view->assertSee('space-y-2', false);
});

it('renders shimmer animation by default', function () {
    $view = $this->blade('<x-kore::skeleton />');
    $view->assertSee('kore-skeleton-shimmer', false);
});

it('renders pulse animation', function () {
    $view = $this->blade('<x-kore::skeleton animation="pulse" />');
    $view->assertSee('animate-pulse', false);
});

it('renders without animation when none', function () {
    $view = $this->blade('<x-kore::skeleton animation="none" />');
    $view->assertDontSee('animate-pulse', false)
        ->assertDontSee('kore-skeleton-shimmer', false);
});

it('renders custom width and height', function () {
    $view = $this->blade('<x-kore::skeleton width="200px" height="50px" />');
    $view->assertSee('width: 200px', false)
        ->assertSee('height: 50px', false);
});

it('renders circle with size shorthand', function () {
    $view = $this->blade('<x-kore::skeleton shape="circle" size="4rem" />');
    $view->assertSee('width: 4rem', false)
        ->assertSee('height: 4rem', false);
});
