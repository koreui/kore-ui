<?php

use KoreUi\Breadcrumbs\BreadcrumbManager;
use KoreUi\Breadcrumbs\BreadcrumbTrail;

it('renders breadcrumbs from array items', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Users', 'url' => '/users'],
            ['label' => 'Detail'],
        ],
    ]);

    $view->assertSee('Home')
        ->assertSee('Users')
        ->assertSee('Detail');
});

it('renders with nav aria-label', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('aria-label="Ruta de navegación"', false);
});

it('marks the last item with aria-current="page"', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Current'],
        ],
    ]);

    $view->assertSee('aria-current="page"', false);
});

it('renders separators with aria-hidden', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" separator="/" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About'],
        ],
    ]);

    $view->assertSee('aria-hidden="true"', false);
});

it('renders text separator', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" separator="/" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About'],
        ],
    ]);

    $view->assertSee('/');
});

it('renders icon separator', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" separator="icon:chevron-right" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About'],
        ],
    ]);

    // Lucide icon component should be rendered
    $view->assertSee('Home')
        ->assertSee('About');
});

it('applies xs size classes', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" size="xs" />', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('text-xs', false)
        ->assertSee('gap-0.5', false);
});

it('applies sm size classes', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" size="sm" />', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('gap-1', false);
});

it('applies lg size classes', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" size="lg" />', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('text-base', false)
        ->assertSee('gap-2', false);
});

it('renders links for items with url', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/home'],
            ['label' => 'Current'],
        ],
    ]);

    $view->assertSee('href="/home"', false);
});

it('renders span without link for current item', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Current'],
        ],
    ]);

    // The current item should not have a link
    $view->assertSee('Current')
        ->assertSee('aria-current="page"', false);
});

it('renders nothing when items is empty array', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [],
    ]);

    $view->assertDontSee('aria-label="Ruta de navegación"', false);
});

it('renders collapsible ellipsis when maxItems is exceeded', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" :max-items="3" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Cat 1', 'url' => '/cat1'],
            ['label' => 'Cat 2', 'url' => '/cat2'],
            ['label' => 'Cat 3', 'url' => '/cat3'],
            ['label' => 'Current'],
        ],
    ]);

    $view->assertSee('Mostrar los pasos ocultos', false)
        ->assertSee('x-on:click="expanded = true"', false);
});

it('does not render collapsible when items fit within maxItems', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" :max-items="5" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'About'],
        ],
    ]);

    $view->assertDontSee('Mostrar los pasos ocultos', false);
});

it('renders JSON-LD structured data when enabled', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" :json-ld="true" />', [
        'items' => [
            ['label' => 'Home', 'url' => 'https://example.com/'],
            ['label' => 'Users', 'url' => 'https://example.com/users'],
            ['label' => 'Detail'],
        ],
    ]);

    $view->assertSee('application/ld+json', false)
        ->assertSee('BreadcrumbList', false)
        ->assertSee('schema.org', false);
});

it('does not render JSON-LD by default', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertDontSee('application/ld+json', false);
});

it('renders left slot', function () {
    $view = $this->blade('
        <x-kore::breadcrumbs :items="$items">
            <x-slot:left>
                <span class="test-left-slot">Left</span>
            </x-slot:left>
        </x-kore::breadcrumbs>
    ', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('test-left-slot', false)
        ->assertSee('Left');
});

it('renders right slot', function () {
    $view = $this->blade('
        <x-kore::breadcrumbs :items="$items">
            <x-slot:right>
                <span class="test-right-slot">Right</span>
            </x-slot:right>
        </x-kore::breadcrumbs>
    ', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('test-right-slot', false)
        ->assertSee('Right');
});

it('renders icon on items', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" />', [
        'items' => [
            ['label' => 'Home', 'url' => '/', 'icon' => 'home'],
        ],
    ]);

    // The lucide icon component should render
    $view->assertSee('Home');
});

it('merges additional attributes on nav', function () {
    $view = $this->blade('<x-kore::breadcrumbs :items="$items" class="my-custom-class" />', [
        'items' => [['label' => 'Home', 'url' => '/']],
    ]);

    $view->assertSee('my-custom-class', false);
});
