<?php

it('renders with label and value', function () {
    $view = $this->blade('<x-kore::radio label="Basic" value="basic" name="plan" />');

    $view->assertSee('Basic')
        ->assertSee('type="radio"', false)
        ->assertSee('value="basic"', false);
});

it('renders with description', function () {
    $view = $this->blade('<x-kore::radio label="Pro" value="pro" name="plan" description="$29/month" />');

    $view->assertSee('Pro')
        ->assertSee('$29/month');
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::radio label="Enterprise" value="enterprise" name="plan" disabled />');

    $view->assertSee('disabled', false)
        ->assertSee('opacity-50', false);
});

it('forwards wire:model', function () {
    $view = $this->blade('<x-kore::radio label="Basic" value="basic" wire:model="plan" />');

    $view->assertSee('wire:model="plan"', false);
});
