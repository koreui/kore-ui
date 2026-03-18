<?php

it('renders a kbd element', function () {
    $view = $this->blade('<x-kore::kbd>K</x-kore::kbd>');
    $view->assertSee('<kbd', false)
        ->assertSee('K');
});

it('renders slot content', function () {
    $view = $this->blade('<x-kore::kbd>Ctrl+C</x-kore::kbd>');
    $view->assertSee('Ctrl+C');
});

it('renders md size by default', function () {
    $view = $this->blade('<x-kore::kbd>A</x-kore::kbd>');
    $view->assertSee('text-xs', false)
        ->assertSee('px-1.5', false);
});

it('renders sm size', function () {
    $view = $this->blade('<x-kore::kbd size="sm">A</x-kore::kbd>');
    $view->assertSee('min-w-[18px]', false);
});

it('renders lg size', function () {
    $view = $this->blade('<x-kore::kbd size="lg">A</x-kore::kbd>');
    $view->assertSee('text-sm', false)
        ->assertSee('min-w-[28px]', false);
});

it('renders border classes', function () {
    $view = $this->blade('<x-kore::kbd>X</x-kore::kbd>');
    $view->assertSee('border-kore-border', false)
        ->assertSee('bg-kore-muted', false);
});

it('renders font-mono', function () {
    $view = $this->blade('<x-kore::kbd>Tab</x-kore::kbd>');
    $view->assertSee('font-mono', false);
});

it('accepts custom attributes', function () {
    $view = $this->blade('<x-kore::kbd class="ml-2">Z</x-kore::kbd>');
    $view->assertSee('ml-2', false);
});
