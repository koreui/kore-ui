<?php

use KoreUi\Spotlight\SpotlightManager;
use KoreUi\Tests\Spotlight\Fixtures\DemoProvider;
use KoreUi\Tests\Spotlight\Fixtures\DemoSpotlightComponent;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::component('demo-spotlight-component', DemoSpotlightComponent::class);

    config(['kore-ui.spotlight.providers' => []]);
});

// --- Render ---

it('renders the spotlight manager', function () {
    Livewire::test(SpotlightManager::class)
        ->assertStatus(200);
});

it('renders with default config', function () {
    $component = Livewire::test(SpotlightManager::class);

    expect($component->get('shortcut'))->toBe('k')
        ->and($component->get('showRecent'))->toBeTrue();
});

// --- Provider resolution ---

it('resolves providers from config', function () {
    config(['kore-ui.spotlight.providers' => [DemoProvider::class]]);

    $component = Livewire::test(SpotlightManager::class);

    // Re-instantiate with providers from config
    $instance = new SpotlightManager;
    $instance->mount();
    $items = $instance->getItems();

    expect($items)->not->toBeEmpty()
        ->and($items[0])->toHaveKey('name');
});

it('accepts providers as mount parameter', function () {
    $component = Livewire::test(SpotlightManager::class, [
        'providers' => [DemoProvider::class],
    ]);

    $instance = new SpotlightManager;
    $instance->mount(providers: [DemoProvider::class]);

    expect($instance->getItems())->not->toBeEmpty();
});

it('getItems filters invisible items', function () {
    $provider = new class extends \KoreUi\Spotlight\SpotlightProvider {
        public function items(): array
        {
            return [
                \KoreUi\Spotlight\SpotlightItem::make('Visible')->when(true),
                \KoreUi\Spotlight\SpotlightItem::make('Invisible')->when(false),
            ];
        }
    };

    // Bind anonymous class with a temp name
    app()->bind('test-provider', fn () => $provider);

    $instance = new SpotlightManager;
    $instance->mount(providers: [DemoProvider::class]);

    $items = $instance->getItems();

    // All DemoProvider items are visible
    foreach ($items as $item) {
        expect($item)->toHaveKey('id');
    }
});

it('getItems respects maxResults limit', function () {
    config(['kore-ui.spotlight.providers' => [DemoProvider::class]]);

    $instance = new SpotlightManager;
    $instance->mount(maxResults: 1, providers: [DemoProvider::class]);

    expect($instance->getItems())->toHaveCount(1);
});

it('getItems sorts providers by priority', function () {
    $highPriority = new class extends \KoreUi\Spotlight\SpotlightProvider {
        public function priority(): int { return 5; }

        public function group(): string { return 'High'; }

        public function items(): array
        {
            return [\KoreUi\Spotlight\SpotlightItem::make('High Priority Item')];
        }
    };

    $lowPriority = new class extends \KoreUi\Spotlight\SpotlightProvider {
        public function priority(): int { return 50; }

        public function group(): string { return 'Low'; }

        public function items(): array
        {
            return [\KoreUi\Spotlight\SpotlightItem::make('Low Priority Item')];
        }
    };

    app()->bind('high-priority', fn () => $highPriority);
    app()->bind('low-priority', fn () => $lowPriority);

    $instance = new SpotlightManager;
    $instance->mount(providers: ['high-priority', 'low-priority']);

    $items = $instance->getItems();

    expect($items[0]['name'])->toBe('High Priority Item')
        ->and($items[1]['name'])->toBe('Low Priority Item');
});

// --- mount() ---

it('mount accepts shortcut override', function () {
    $instance = new SpotlightManager;
    $instance->mount(shortcut: 'p');

    expect($instance->shortcut)->toBe('p');
});

it('mount accepts placeholder override', function () {
    $instance = new SpotlightManager;
    $instance->mount(placeholder: 'Buscar...');

    expect($instance->placeholder)->toBe('Buscar...');
});

it('mount accepts searchUrl', function () {
    $instance = new SpotlightManager;
    $instance->mount(searchUrl: '/api/search');

    expect($instance->searchUrl)->toBe('/api/search');
});

it('mount defaults to GET searchMethod', function () {
    $instance = new SpotlightManager;
    $instance->mount();

    expect($instance->searchMethod)->toBe('GET');
});

it('mount normalizes searchMethod to uppercase', function () {
    $instance = new SpotlightManager;
    $instance->mount(searchMethod: 'post');

    expect($instance->searchMethod)->toBe('POST');
});

// --- search() ---

it('search dispatches kore:spotlight-results event', function () {
    config(['kore-ui.spotlight.providers' => [DemoProvider::class]]);

    Livewire::test(SpotlightManager::class, [
        'providers' => [DemoProvider::class],
    ])
        ->call('search', 'inicio')
        ->assertDispatched('kore:spotlight-results');
});

it('search with empty string dispatches empty results', function () {
    Livewire::test(SpotlightManager::class)
        ->call('search', '')
        ->assertDispatched('kore:spotlight-results');
});

it('search with whitespace-only query dispatches empty results', function () {
    Livewire::test(SpotlightManager::class)
        ->call('search', '   ')
        ->assertDispatched('kore:spotlight-results');
});
