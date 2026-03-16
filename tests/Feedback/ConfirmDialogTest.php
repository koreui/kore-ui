<?php

use KoreUi\Core\Contracts\Overlayable;
use KoreUi\Feedback\ConfirmDialog;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::component('kore-confirm-dialog', ConfirmDialog::class);
});

it('implements Overlayable interface', function () {
    expect(new ConfirmDialog)->toBeInstanceOf(Overlayable::class);
});

it('has confirm overlay type', function () {
    expect(ConfirmDialog::overlayType())->toBe('confirm');
});

it('destroys on close by default', function () {
    expect(ConfirmDialog::destroysOnClose())->toBeTrue();
});

it('renders with title and description', function () {
    Livewire::test(ConfirmDialog::class, [
        'title' => 'Delete item?',
        'description' => 'This cannot be undone.',
        'confirmText' => 'Delete',
        'cancelText' => 'Cancel',
    ])
        ->assertSee('Delete item?')
        ->assertSee('This cannot be undone.')
        ->assertSee('Delete')
        ->assertSee('Cancel');
});

it('dispatches confirm callback on accept', function () {
    Livewire::test(ConfirmDialog::class, [
        'title' => 'Delete?',
        'onConfirm' => ['method' => 'delete', 'params' => [42]],
        'callerRef' => 'test-ref',
    ])
        ->call('accept')
        ->assertDispatched('kore:confirm-callback',
            method: 'delete',
            params: [42],
            callerRef: 'test-ref',
        )
        ->assertDispatched('kore:close');
});

it('dispatches cancel callback on reject', function () {
    Livewire::test(ConfirmDialog::class, [
        'title' => 'Delete?',
        'onCancel' => ['method' => 'keepEditing', 'params' => []],
        'callerRef' => 'test-ref',
    ])
        ->call('reject')
        ->assertDispatched('kore:confirm-callback',
            method: 'keepEditing',
            params: [],
            callerRef: 'test-ref',
        )
        ->assertDispatched('kore:close');
});

it('closes without dispatching callback when no onConfirm set', function () {
    Livewire::test(ConfirmDialog::class, [
        'title' => 'Delete?',
    ])
        ->call('accept')
        ->assertNotDispatched('kore:confirm-callback')
        ->assertDispatched('kore:close');
});

it('closes without dispatching callback when no onCancel set', function () {
    Livewire::test(ConfirmDialog::class, [
        'title' => 'Delete?',
    ])
        ->call('reject')
        ->assertNotDispatched('kore:confirm-callback')
        ->assertDispatched('kore:close');
});
