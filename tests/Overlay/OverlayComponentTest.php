<?php

use KoreUi\Core\Contracts\Overlayable;
use KoreUi\Tests\Overlay\Fixtures\CustomOverlay;
use KoreUi\Tests\Overlay\Fixtures\DemoOverlay;
use Livewire\Livewire;

it('implements Overlayable interface', function () {
    expect(new DemoOverlay)->toBeInstanceOf(Overlayable::class);
});

it('uses config defaults when not overridden', function () {
    expect(DemoOverlay::overlayType())->toBe('modal')
        ->and(DemoOverlay::overlaySize())->toBe('2xl')
        ->and(DemoOverlay::overlayPosition())->toBe('center')
        ->and(DemoOverlay::closesOnClickAway())->toBeTrue()
        ->and(DemoOverlay::closesOnEscape())->toBeTrue()
        ->and(DemoOverlay::escapeClosesAll())->toBeTrue()
        ->and(DemoOverlay::dispatchesCloseEvent())->toBeFalse()
        ->and(DemoOverlay::destroysOnClose())->toBeFalse()
        ->and(DemoOverlay::backdropBlur())->toBeFalse();
});

it('allows overriding defaults per component', function () {
    expect(CustomOverlay::overlayType())->toBe('drawer')
        ->and(CustomOverlay::overlaySize())->toBe('lg')
        ->and(CustomOverlay::overlayPosition())->toBe('right')
        ->and(CustomOverlay::closesOnClickAway())->toBeFalse()
        ->and(CustomOverlay::escapeClosesAll())->toBeFalse()
        ->and(CustomOverlay::destroysOnClose())->toBeTrue()
        ->and(CustomOverlay::backdropBlur())->toBeTrue();
});

it('dispatches close event', function () {
    Livewire::component('demo-overlay', DemoOverlay::class);

    Livewire::test(DemoOverlay::class)
        ->call('close')
        ->assertDispatched('kore:close');
});

it('dispatches closeAll event with force', function () {
    Livewire::component('demo-overlay', DemoOverlay::class);

    Livewire::test(DemoOverlay::class)
        ->call('closeAll')
        ->assertDispatched('kore:close', force: true);
});

it('dispatches skipBack event', function () {
    Livewire::component('demo-overlay', DemoOverlay::class);

    Livewire::test(DemoOverlay::class)
        ->call('skipBack', count: 2, destroy: true)
        ->assertDispatched('kore:close', skipPrevious: 2, destroySkipped: true);
});
