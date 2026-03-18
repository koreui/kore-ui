<?php

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::color-picker label="Color" name="color" />');

    $view->assertSee('Color')
        ->assertSee('name="color"', false);
});

it('renders KoreColorPicker Alpine data', function () {
    $view = $this->blade('<x-kore::color-picker name="color" />');

    $view->assertSee('KoreColorPicker', false);
});

it('renders default palette swatches', function () {
    $view = $this->blade('<x-kore::color-picker name="color" inline />');

    $view->assertSee('background-color: #ef4444', false)
        ->assertSee('background-color: #3b82f6', false);
});

it('renders custom colors', function () {
    $view = $this->blade('<x-kore::color-picker name="color" :colors="[\'#ff0000\', \'#00ff00\']" inline />');

    $view->assertSee('background-color: #ff0000', false)
        ->assertSee('background-color: #00ff00', false);
});

it('renders hex input when allowCustom', function () {
    $view = $this->blade('<x-kore::color-picker name="color" inline />');

    $view->assertSee('placeholder="#000000"', false);
});

it('hides hex input when allowCustom is false', function () {
    $view = $this->blade('<x-kore::color-picker name="color" :allow-custom="false" inline />');

    $view->assertDontSee('applyCustom()', false);
});

it('renders inline mode', function () {
    $view = $this->blade('<x-kore::color-picker name="color" inline />');

    $view->assertDontSee('x-teleport', false);
});

it('renders dropdown mode', function () {
    $view = $this->blade('<x-kore::color-picker name="color" />');

    $view->assertSee('x-teleport', false);
});

it('renders clearable button', function () {
    $view = $this->blade('<x-kore::color-picker name="color" />');

    $view->assertSee('clear()', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::color-picker name="color" disabled />');

    $view->assertSee('opacity-50', false);
});

it('forwards wire:model on hidden input', function () {
    $view = $this->blade('<x-kore::color-picker wire:model="color" />');

    $view->assertSee('wire:model="color"', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['color' => 'Please select a color.']);

    $view = $this->blade('<x-kore::color-picker label="Color" name="color" />');

    $view->assertSee('Please select a color.')
        ->assertSee('role="alert"', false);
});

it('renders required indicator', function () {
    $view = $this->blade('<x-kore::color-picker label="Color" name="color" required />');

    $view->assertSee('*');
});

it('renders custom columns', function () {
    $view = $this->blade('<x-kore::color-picker name="color" :columns="6" inline />');

    $view->assertSee('repeat(6, minmax(0, 1fr))', false);
});

it('shows hint when no error', function () {
    $view = $this->blade('<x-kore::color-picker label="Color" name="color" hint="Pick your brand color" />');

    $view->assertSee('Pick your brand color');
});
