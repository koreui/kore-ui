<?php

it('renders toolbar', function () {
    $view = $this->blade('<x-kore::toolbar>Content</x-kore::toolbar>');
    $view->assertSee('role="toolbar"', false);
});

it('renders default variant', function () {
    $view = $this->blade('<x-kore::toolbar>Content</x-kore::toolbar>');
    $view->assertSee('px-1', false);
});

it('renders bordered variant', function () {
    $view = $this->blade('<x-kore::toolbar variant="bordered">Content</x-kore::toolbar>');
    $view->assertSee('border-kore-border', false);
});

it('renders start slot', function () {
    $view = $this->blade('
        <x-kore::toolbar>
            <x-slot:start>Start content</x-slot:start>
        </x-kore::toolbar>
    ');
    $view->assertSee('Start content');
});

it('renders end slot', function () {
    $view = $this->blade('
        <x-kore::toolbar>
            <x-slot:end>End content</x-slot:end>
        </x-kore::toolbar>
    ');
    $view->assertSee('End content');
});

it('renders justify center', function () {
    $view = $this->blade('<x-kore::toolbar justify="center">Content</x-kore::toolbar>');
    $view->assertSee('justify-center', false);
});
