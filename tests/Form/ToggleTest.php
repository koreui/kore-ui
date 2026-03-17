<?php

it('renders with label', function () {
    $view = $this->blade('<x-kore::toggle label="Dark mode" name="dark" />');

    $view->assertSee('Dark mode')
        ->assertSee('role="switch"', false);
});

it('renders with description', function () {
    $view = $this->blade('<x-kore::toggle label="Maintenance" description="Only admins can access" name="maintenance" />');

    $view->assertSee('Maintenance')
        ->assertSee('Only admins can access');
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::toggle label="Test" name="test" size="sm" />');

    $view->assertSee('h-5', false)
        ->assertSee('w-9', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::toggle label="Test" name="test" size="lg" />');

    $view->assertSee('h-7', false)
        ->assertSee('w-14', false);
});

it('renders on/off labels', function () {
    $view = $this->blade('<x-kore::toggle label="Status" name="status" on-label="On" off-label="Off" />');

    $view->assertSee('On')
        ->assertSee('Off');
});

it('renders label on left', function () {
    $view = $this->blade('<x-kore::toggle label="Active" name="active" label-position="left" />');

    $view->assertSee('flex-row-reverse', false);
});

it('renders hidden checkbox for wire:model', function () {
    $view = $this->blade('<x-kore::toggle label="Active" wire:model="active" />');

    $view->assertSee('wire:model="active"', false)
        ->assertSee('type="checkbox"', false);
});
