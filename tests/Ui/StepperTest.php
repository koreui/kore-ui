<?php

it('renders stepper container', function () {
    $view = $this->blade('
        <x-kore::stepper>
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('Content');
});

it('renders with x-data KoreStepper', function () {
    $view = $this->blade('
        <x-kore::stepper>
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('KoreStepper', false);
});

it('renders step item', function () {
    $view = $this->blade('<x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>');
    $view->assertSee('Content');
});

it('renders horizontal variant by default', function () {
    $view = $this->blade('
        <x-kore::stepper>
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('flex items-center', false);
});

it('renders vertical variant', function () {
    $view = $this->blade('
        <x-kore::stepper variant="vertical">
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('flex-col', false);
});

it('renders compact variant', function () {
    $view = $this->blade('
        <x-kore::stepper variant="compact">
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('mb-4', false);
});

it('renders linear mode', function () {
    $view = $this->blade('
        <x-kore::stepper :linear="true">
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('linear: true', false);
});

it('renders step with label', function () {
    $view = $this->blade('<x-kore::stepper.item id="s1" label="Account Setup">Content</x-kore::stepper.item>');
    $view->assertSee("label: 'Account Setup'", false);
});

it('renders step with description', function () {
    $view = $this->blade('<x-kore::stepper.item id="s1" label="Step 1" description="Enter details">Content</x-kore::stepper.item>');
    $view->assertSee("description: 'Enter details'", false);
});

it('renders step with icon', function () {
    $view = $this->blade('<x-kore::stepper.item id="s1" label="Step 1" icon="user">Content</x-kore::stepper.item>');
    $view->assertSee('<svg', false);
});

it('renders disabled step', function () {
    $view = $this->blade('<x-kore::stepper.item id="s1" label="Step 1" :disabled="true">Content</x-kore::stepper.item>');
    $view->assertSee('disabled: true', false);
});

it('renders step with error status', function () {
    $view = $this->blade('<x-kore::stepper.item id="s1" label="Step 1" status="error">Content</x-kore::stepper.item>');
    $view->assertSee("status: 'error'", false);
});

it('renders hidden input for wire model', function () {
    $view = $this->blade('
        <x-kore::stepper>
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
        </x-kore::stepper>
    ');
    $view->assertSee('type="hidden"', false);
});

it('renders navigation slot', function () {
    $view = $this->blade('
        <x-kore::stepper>
            <x-kore::stepper.item id="s1" label="Step 1">Content</x-kore::stepper.item>
            <x-slot:navigation>
                <button>Next</button>
            </x-slot:navigation>
        </x-kore::stepper>
    ');
    $view->assertSee('Next');
});

/**
 * Los pasos eran `div` sueltos con un `aria-current="step"` encima: un lector no
 * decía cuántos hay ni por cuál va, y el «paso actual» lo era de una lista que
 * no existía. Y el botón solo enseña un número, así que se anunciaba «1, botón».
 */
it('los pasos son una lista y cada botón dice de qué paso es', function () {
    foreach (['horizontal', 'vertical', 'compact'] as $variante) {
        $view = $this->blade('<x-kore::stepper variant="'.$variante.'" />');

        $view->assertSee('role="list"', false)
            ->assertSee('role="listitem"', false)
            ->assertSee('x-bind:aria-label="step.label ||', false);
    }
});
