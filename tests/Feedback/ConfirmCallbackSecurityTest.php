<?php

use KoreUi\Tests\Feedback\Fixtures\DemoComponent;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::component('demo-component', DemoComponent::class);
});

// C1 — A forged kore:confirm-callback event (dispatchable from the browser)
// must not be able to invoke an arbitrary or protected method, bypassing the
// confirm dialog. Only methods authorized server-side by confirm()->send() run.

it('ignores a forged confirm-callback for an unauthorized protected method', function () {
    $component = Livewire::test(DemoComponent::class);

    $component->dispatch('kore:confirm-callback',
        method: 'secretAction',
        params: [],
        callerRef: $component->id(),
    );

    expect($component->get('secretRun'))->toBeFalse();
});

it('ignores a forged confirm-callback for an unauthorized public method', function () {
    $component = Livewire::test(DemoComponent::class);

    // 'handleConfirm' is public, but it was never authorized via confirm()->send().
    $component->dispatch('kore:confirm-callback',
        method: 'handleConfirm',
        params: [99],
        callerRef: $component->id(),
    );

    expect($component->get('message'))->toBe('');
});

it('executes a confirm-callback once confirm()->send() has authorized it', function () {
    $component = Livewire::test(DemoComponent::class);

    $component->call('sendConfirm'); // authorizes 'handleConfirm'

    $component->dispatch('kore:confirm-callback',
        method: 'handleConfirm',
        params: [1],
        callerRef: $component->id(),
    );

    expect($component->get('message'))->toBe('Confirmed: 1');
});

it('consumes the authorization so a replayed callback runs only once', function () {
    $component = Livewire::test(DemoComponent::class);
    $component->call('sendConfirm');

    $component->dispatch('kore:confirm-callback', method: 'handleConfirm', params: [1], callerRef: $component->id());
    $component->set('message', '');

    // Replay of the same callback: authorization already consumed → no-op.
    $component->dispatch('kore:confirm-callback', method: 'handleConfirm', params: [1], callerRef: $component->id());

    expect($component->get('message'))->toBe('');
});
