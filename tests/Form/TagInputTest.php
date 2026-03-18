<?php

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::tag-input label="Skills" name="skills" />');

    $view->assertSee('Skills')
        ->assertSee('name="skills"', false);
});

it('renders KoreTagInput Alpine data', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" />');

    $view->assertSee('KoreTagInput', false);
});

it('renders hidden input for wire:model', function () {
    $view = $this->blade('<x-kore::tag-input wire:model="skills" />');

    $view->assertSee('wire:model="skills"', false)
        ->assertSee('type="hidden"', false);
});

it('renders placeholder', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" placeholder="Add a skill..." />');

    $view->assertSee('Add a skill...');
});

it('renders clearable button', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" clearable />');

    $view->assertSee('clearAll()', false);
});

it('does not render clearable when not set', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" />');

    $view->assertDontSee('clearAll()', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" disabled />');

    $view->assertSee('opacity-50', false)
        ->assertSee('disabled', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" size="sm" />');

    $view->assertSee('text-xs', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" size="lg" />');

    $view->assertSee('text-base', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['skills' => 'At least one skill is required.']);

    $view = $this->blade('<x-kore::tag-input label="Skills" name="skills" />');

    $view->assertSee('At least one skill is required.')
        ->assertSee('role="alert"', false);
});

it('shows manual error prop', function () {
    $view = $this->blade('<x-kore::tag-input label="Skills" name="skills" error="Invalid skills" />');

    $view->assertSee('Invalid skills')
        ->assertSee('role="alert"', false);
});

it('renders required indicator', function () {
    $view = $this->blade('<x-kore::tag-input label="Skills" name="skills" required />');

    $view->assertSee('*');
});

it('passes separator config', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" separator=";" />');

    $view->assertSee('separator', false);
});

it('passes max config', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" :max="5" />');

    $view->assertSee('&quot;max&quot;:5', false);
});

it('passes allowDuplicate config', function () {
    $view = $this->blade('<x-kore::tag-input name="skills" allow-duplicate />');

    $view->assertSee('allowDuplicate', false);
});

it('shows hint when no error', function () {
    $view = $this->blade('<x-kore::tag-input label="Skills" name="skills" hint="Press Enter to add" />');

    $view->assertSee('Press Enter to add');
});
