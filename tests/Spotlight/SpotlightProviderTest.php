<?php

use KoreUi\Spotlight\SpotlightItem;
use KoreUi\Spotlight\SpotlightProvider;
use KoreUi\Tests\Spotlight\Fixtures\DemoProvider;

// --- Base contract ---

it('DemoProvider has the correct group and priority', function () {
    $provider = new DemoProvider;

    expect($provider->group())->toBe('Demo')
        ->and($provider->priority())->toBe(10);
});

it('DemoProvider items returns SpotlightItem instances', function () {
    $provider = new DemoProvider;
    $items = $provider->items();

    expect($items)->toBeArray()->not->toBeEmpty();
    foreach ($items as $item) {
        expect($item)->toBeInstanceOf(SpotlightItem::class);
    }
});

it('default search returns items() result', function () {
    $provider = new DemoProvider;

    expect($provider->search('inicio'))->toEqual($provider->items());
});

it('toArray serializes visible items to arrays', function () {
    $provider = new DemoProvider;
    $data = $provider->toArray();

    expect($data)->toBeArray()->not->toBeEmpty();
    foreach ($data as $item) {
        expect($item)->toBeArray()
            ->toHaveKey('id')
            ->toHaveKey('name')
            ->toHaveKey('group');
    }
});

it('toArray applies provider group when item has default General group', function () {
    $provider = new DemoProvider; // group = 'Demo'
    $data = $provider->toArray();

    // All items in DemoProvider don't set a custom group, so they inherit 'Demo'
    foreach ($data as $item) {
        expect($item['group'])->toBe('Demo');
    }
});

it('toArray respects item group when explicitly set', function () {
    $provider = new class extends SpotlightProvider {
        public function group(): string { return 'Provider Group'; }

        public function items(): array
        {
            return [
                SpotlightItem::make('Custom Group Item')->group('Custom'),
                SpotlightItem::make('Default Group Item'),
            ];
        }
    };

    $data = $provider->toArray();

    expect($data[0]['group'])->toBe('Custom')     // Explicit group preserved
        ->and($data[1]['group'])->toBe('Provider Group'); // Default overridden
});

it('toArray filters out invisible items', function () {
    $provider = new class extends SpotlightProvider {
        public function items(): array
        {
            return [
                SpotlightItem::make('Visible')->when(true),
                SpotlightItem::make('Hidden')->when(false),
            ];
        }
    };

    $data = $provider->toArray();

    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Visible');
});

it('base provider has default priority of 100', function () {
    $provider = new class extends SpotlightProvider {
        public function items(): array { return []; }
    };

    expect($provider->priority())->toBe(100);
});

it('base provider has default group of General', function () {
    $provider = new class extends SpotlightProvider {
        public function items(): array { return []; }
    };

    expect($provider->group())->toBe('General');
});

it('base provider items returns empty array by default', function () {
    $provider = new class extends SpotlightProvider {};

    expect($provider->items())->toBe([]);
});
