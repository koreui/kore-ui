<?php

it('renders with label and slot', function () {
    $view = $this->blade('
        <x-kore::radio-group label="Plan">
            <x-kore::radio label="Basic" value="basic" name="plan" />
        </x-kore::radio-group>
    ');

    $view->assertSee('Plan')
        ->assertSee('Basic')
        ->assertSee('role="radiogroup"', false);
});

it('renders inline layout', function () {
    $view = $this->blade('
        <x-kore::radio-group label="Plan" inline>
            <x-kore::radio label="A" value="a" name="plan" />
            <x-kore::radio label="B" value="b" name="plan" />
        </x-kore::radio-group>
    ');

    $view->assertSee('flex', false)
        ->assertSee('gap-4', false);
});

it('renders vertical layout by default', function () {
    $view = $this->blade('
        <x-kore::radio-group label="Plan">
            <x-kore::radio label="A" value="a" name="plan" />
        </x-kore::radio-group>
    ');

    $view->assertSee('space-y-2', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['plan' => 'Please select a plan']);

    $view = $this->blade('
        <x-kore::radio-group label="Plan" name="plan">
            <x-kore::radio label="A" value="a" name="plan" />
        </x-kore::radio-group>
    ');

    $view->assertSee('Please select a plan');
});
