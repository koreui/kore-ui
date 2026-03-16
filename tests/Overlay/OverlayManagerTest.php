<?php

use KoreUi\Overlay\OverlayManager;
use KoreUi\Tests\Overlay\Fixtures\CustomOverlay;
use KoreUi\Tests\Overlay\Fixtures\DemoOverlay;
use KoreUi\Tests\Overlay\Fixtures\InvalidOverlay;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::component('demo-overlay', DemoOverlay::class);
    Livewire::component('custom-overlay', CustomOverlay::class);
    Livewire::component('invalid-overlay', InvalidOverlay::class);
});

it('opens an overlay', function () {
    Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay')
        ->assertNotSet('current', null)
        ->assertDispatched('kore:overlay-changed');
});

it('stores overlay data correctly', function () {
    $component = Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay');

    $overlays = $component->get('overlays');

    expect($overlays)->toHaveCount(1);

    $first = array_values($overlays)[0];

    expect($first['name'])->toBe('demo-overlay')
        ->and($first['arguments'])->toBe([])
        ->and($first['overlayAttributes']['type'])->toBe('modal')
        ->and($first['overlayAttributes']['size'])->toBe('2xl');
});

it('maintains stack when opening nested overlays', function () {
    Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay')
        ->dispatch('kore:open', component: 'custom-overlay')
        ->assertCount('overlays', 2);
});

it('respects custom overlay attributes', function () {
    $component = Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'custom-overlay');

    $overlays = $component->get('overlays');
    $attrs = array_values($overlays)[0]['overlayAttributes'];

    expect($attrs['type'])->toBe('drawer')
        ->and($attrs['size'])->toBe('lg')
        ->and($attrs['position'])->toBe('right')
        ->and($attrs['closesOnClickAway'])->toBeFalse()
        ->and($attrs['destroysOnClose'])->toBeTrue()
        ->and($attrs['backdropBlur'])->toBeTrue();
});

it('throws exception when component does not implement Overlayable', function () {
    Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'invalid-overlay');
})->throws(InvalidArgumentException::class);

it('destroys an overlay', function () {
    $component = Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay');

    $id = $component->get('current');

    $component->dispatch('kore:destroy', id: $id)
        ->assertCount('overlays', 0)
        ->assertSet('current', null);
});

it('resets state', function () {
    Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay')
        ->dispatch('kore:open', component: 'custom-overlay')
        ->call('resetState')
        ->assertCount('overlays', 0)
        ->assertSet('current', null);
});

it('generates unique ids for same component with same arguments', function () {
    $component = Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay')
        ->dispatch('kore:open', component: 'demo-overlay');

    $overlays = $component->get('overlays');

    expect($overlays)->toHaveCount(2);

    $ids = array_keys($overlays);

    expect($ids[0])->not->toBe($ids[1]);
});

it('passes arguments to overlay', function () {
    $component = Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay', arguments: ['title' => 'Custom Title']);

    $overlays = $component->get('overlays');
    $first = array_values($overlays)[0];

    expect($first['arguments'])->toBe(['title' => 'Custom Title']);
});

it('allows overlay attribute overrides at open time', function () {
    $component = Livewire::test(OverlayManager::class)
        ->dispatch('kore:open', component: 'demo-overlay', overlayAttributes: ['backdropBlur' => true]);

    $overlays = $component->get('overlays');
    $attrs = array_values($overlays)[0]['overlayAttributes'];

    expect($attrs['backdropBlur'])->toBeTrue();
});
