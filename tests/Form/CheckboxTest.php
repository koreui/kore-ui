<?php

it('renders with label', function () {
    $view = $this->blade('<x-kore::checkbox label="Accept terms" name="terms" />');

    $view->assertSee('Accept terms')
        ->assertSee('type="checkbox"', false);
});

it('renders with description', function () {
    $view = $this->blade('<x-kore::checkbox label="Notifications" description="Get weekly updates" name="notify" />');

    $view->assertSee('Notifications')
        ->assertSee('Get weekly updates');
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::checkbox label="Remember" name="remember" size="sm" />');

    $view->assertSee('size-3.5', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::checkbox label="Remember" name="remember" size="lg" />');

    $view->assertSee('size-5', false);
});

it('renders label on left', function () {
    $view = $this->blade('<x-kore::checkbox label="Remember" name="remember" label-position="left" />');

    $view->assertSee('flex-row-reverse', false);
});

it('forwards wire:model', function () {
    $view = $this->blade('<x-kore::checkbox label="Accept" wire:model="accepted" />');

    $view->assertSee('wire:model="accepted"', false);
});

it('renders indeterminate state', function () {
    $view = $this->blade('<x-kore::checkbox label="Select all" name="all" indeterminate />');

    $view->assertSee('indeterminate', false);
});
