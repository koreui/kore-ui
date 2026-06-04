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

it('does not render aria-checked', function () {
    // role="switch" on a native checkbox takes its state from the `checked`
    // attribute; ARIA in HTML forbids aria-checked here. Guard the decision.
    $view = $this->blade('<x-kore::toggle label="Active" name="active" />');

    $view->assertDontSee('aria-checked', false);
});

it('marks on/off labels as decorative so they do not pollute the accessible name', function () {
    $view = $this->blade('<x-kore::toggle label="Status" name="status" on-label="On" off-label="Off" />');

    $view->assertSee('aria-hidden="true"', false);
});

it('forwards aria-label to the input', function () {
    $view = $this->blade('<x-kore::toggle name="active" aria-label="Enable feature" />');

    $view->assertSee('aria-label="Enable feature"', false);
});
