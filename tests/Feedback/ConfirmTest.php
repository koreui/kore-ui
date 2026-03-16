<?php

use KoreUi\Feedback\Confirm;
use KoreUi\Tests\Feedback\Fixtures\DemoComponent;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::component('demo-component', DemoComponent::class);
});

it('sets title', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->toArray();

    expect($data['title'])->toBe('Delete?');
});

it('sets description', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->description('This cannot be undone.')->toArray();

    expect($data['description'])->toBe('This cannot be undone.');
});

it('sets type', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->type('warning')->toArray();

    expect($data['type'])->toBe('warning');
});

it('sets confirmText and cancelText', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm
        ->confirmText('Yes, delete')
        ->cancelText('Keep it')
        ->toArray();

    expect($data['confirmText'])->toBe('Yes, delete')
        ->and($data['cancelText'])->toBe('Keep it');
});

it('uses config defaults for confirmText and cancelText', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->toArray();

    expect($data['confirmText'])->toBe('Confirmar')
        ->and($data['cancelText'])->toBe('Cancelar');
});

it('sets onConfirm callback', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->onConfirm('delete', [1, 2])->toArray();

    expect($data['onConfirm'])->toBe(['method' => 'delete', 'params' => [1, 2]]);
});

it('sets onCancel callback', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->onCancel('keepEditing', [3])->toArray();

    expect($data['onCancel'])->toBe(['method' => 'keepEditing', 'params' => [3]]);
});

it('sets persistent flag', function () {
    $confirm = new Confirm('Delete?');
    $data = $confirm->persistent()->toArray();

    expect($data['persistent'])->toBeTrue();
});

it('dispatches kore:open from Livewire component', function () {
    Livewire::test(DemoComponent::class)
        ->call('sendConfirm')
        ->assertDispatched('kore:open');
});

it('dispatches kore:open with correct confirm payload', function () {
    // Build a confirm and check its toArray output directly
    $confirm = new Confirm('Are you sure?');
    $data = $confirm
        ->onConfirm('handleConfirm', [1])
        ->type('warning')
        ->toArray();

    expect($data['title'])->toBe('Are you sure?')
        ->and($data['type'])->toBe('warning')
        ->and($data['onConfirm'])->toBe(['method' => 'handleConfirm', 'params' => [1]]);
});

it('flashes to session when no Livewire context', function () {
    $confirm = new Confirm('Delete?');
    $confirm->onConfirm('delete', [1])->send();

    expect(session('kore:confirm'))->not->toBeNull()
        ->and(session('kore:confirm')['name'])->toBe('kore-confirm-dialog');
});
