<?php

it('renders with slot content', function () {
    $view = $this->blade('<x-kore::card>Card content</x-kore::card>');
    $view->assertSee('Card content');
});

it('renders with title', function () {
    $view = $this->blade('<x-kore::card title="My Card">Content</x-kore::card>');
    $view->assertSee('My Card');
});

it('renders with subtitle', function () {
    $view = $this->blade('<x-kore::card title="Card" subtitle="Description">Content</x-kore::card>');
    $view->assertSee('Description');
});

it('renders border by default', function () {
    $view = $this->blade('<x-kore::card>Content</x-kore::card>');
    $view->assertSee('border-kore-border', false);
});

it('renders without border', function () {
    $view = $this->blade('<x-kore::card :bordered="false">Content</x-kore::card>');
    $view->assertDontSee('border-kore-border', false);
});

it('renders shadow by default', function () {
    $view = $this->blade('<x-kore::card>Content</x-kore::card>');
    $view->assertSee('shadow-sm', false);
});

it('renders as link when href provided', function () {
    $view = $this->blade('<x-kore::card href="/test">Content</x-kore::card>');
    $view->assertSee('<a', false)
        ->assertSee('href="/test"', false);
});

it('renders footer slot', function () {
    $view = $this->blade('
        <x-kore::card>
            Content
            <x-slot:footer>Footer text</x-slot:footer>
        </x-kore::card>
    ');
    $view->assertSee('Footer text');
});

it('renders collapsible card', function () {
    $view = $this->blade('<x-kore::card title="Collapsible" collapsible>Content</x-kore::card>');
    $view->assertSee('x-data=', false)
        ->assertSee('grid-rows-', false);
});

it('renders image', function () {
    $view = $this->blade('<x-kore::card image="/img.jpg">Content</x-kore::card>');
    $view->assertSee('src="/img.jpg"', false);
});

it('renders loading state', function () {
    $view = $this->blade('<x-kore::card :loading="true">Content</x-kore::card>');
    $view->assertSee('animate-spin', false);
});

it('renders surface background', function () {
    $view = $this->blade('<x-kore::card>Content</x-kore::card>');
    $view->assertSee('bg-kore-surface', false);
});
