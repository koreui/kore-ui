<?php

it('renders with label and attributes', function () {
    $view = $this->blade('<x-kore::textarea label="Description" name="description" placeholder="Enter description" />');

    $view->assertSee('Description')
        ->assertSee('name="description"', false)
        ->assertSee('placeholder="Enter description"', false);
});

it('renders with correct rows', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" rows="8" />');

    $view->assertSee('rows="8"', false);
});

it('uses default rows from config', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" />');

    $view->assertSee('rows="4"', false);
});

it('renders auto-resize attributes', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" auto-resize />');

    $view->assertSee('x-ref="textarea"', false)
        ->assertSee('resize-none', false);
});

it('renders character counter with max-length', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" :max-length="500" />');

    $view->assertSee('maxlength="500"', false)
        ->assertSee('/500', false);
});

it('forwards wire:model', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" wire:model="bio" />');

    $view->assertSee('wire:model="bio"', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['bio' => 'Too long']);

    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" />');

    $view->assertSee('Too long')
        ->assertSee('role="alert"', false);
});
