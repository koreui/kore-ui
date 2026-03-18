<?php

it('renders with x-data and event listener', function () {
    $view = $this->blade('<x-kore::page-loading />');
    $view->assertSee('x-data=', false)
        ->assertSee('kore-page-loading.window', false);
});

it('renders with role status', function () {
    $view = $this->blade('<x-kore::page-loading />');
    $view->assertSee('role="status"', false);
});

it('renders fixed fullscreen overlay', function () {
    $view = $this->blade('<x-kore::page-loading />');
    $view->assertSee('fixed inset-0', false);
});

it('renders blur by default', function () {
    $view = $this->blade('<x-kore::page-loading />');
    $view->assertSee('backdrop-blur-sm', false);
});

it('renders without blur', function () {
    $view = $this->blade('<x-kore::page-loading :blur="false" />');
    $view->assertDontSee('backdrop-blur-sm', false);
});

it('renders loading indicator', function () {
    $view = $this->blade('<x-kore::page-loading />');
    $view->assertSee('animate-spin', false);
});

it('renders dots type', function () {
    $view = $this->blade('<x-kore::page-loading type="dots" />');
    $view->assertSee('animate-bounce', false);
});

it('listens to close event', function () {
    $view = $this->blade('<x-kore::page-loading />');
    $view->assertSee('kore-page-loading-close.window', false);
});
