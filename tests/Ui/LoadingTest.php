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
