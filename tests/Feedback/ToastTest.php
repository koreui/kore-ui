<?php

use KoreUi\Feedback\Config\FeedbackDefaults;
use KoreUi\Feedback\Toast;
use KoreUi\Tests\Feedback\Fixtures\DemoComponent;
use Livewire\Livewire;

// --- Builder fluent API ---

it('returns same instance for chaining', function () {
    $toast = new Toast;
    $result = $toast->success('OK');

    expect($result)->toBe($toast);
});

it('sets success type with correct defaults', function () {
    $toast = new Toast;
    $data = $toast->success('Guardado')->toArray();

    expect($data['type'])->toBe('success')
        ->and($data['title'])->toBe('Guardado')
        ->and($data['timeout'])->toBe(5);
});

it('sets error type as persistent by default', function () {
    $toast = new Toast;
    $data = $toast->error('Fallo')->toArray();

    expect($data['type'])->toBe('error')
        ->and($data['timeout'])->toBe(0);
});

it('sets warning type with 8s timeout', function () {
    $toast = new Toast;
    $data = $toast->warning('Cuidado')->toArray();

    expect($data['type'])->toBe('warning')
        ->and($data['timeout'])->toBe(8);
});

it('sets info type with 5s timeout', function () {
    $toast = new Toast;
    $data = $toast->info('Info')->toArray();

    expect($data['type'])->toBe('info')
        ->and($data['timeout'])->toBe(5);
});

it('sets question type as persistent', function () {
    $toast = new Toast;
    $data = $toast->question('Sure?')->toArray();

    expect($data['type'])->toBe('question')
        ->and($data['timeout'])->toBe(0);
});

it('sets loading type as persistent, not dismissible, noGroup', function () {
    $toast = new Toast;
    $data = $toast->loading('Processing...')->toArray();

    expect($data['type'])->toBe('loading')
        ->and($data['timeout'])->toBe(0)
        ->and($data['dismissible'])->toBeFalse()
        ->and($data['noGroup'])->toBeTrue();
});

it('includes description in payload', function () {
    $toast = new Toast;
    $data = $toast->success('Title', 'Description text')->toArray();

    expect($data['description'])->toBe('Description text')
        ->and($data['expandable'])->toBeTrue()
        ->and($data['autoExpand'])->toBeTrue();
});

it('allows custom timeout', function () {
    $toast = new Toast;
    $data = $toast->info('Test')->timeout(10)->toArray();

    expect($data['timeout'])->toBe(10);
});

it('allows persistent override', function () {
    $toast = new Toast;
    $data = $toast->success('Test')->persistent()->toArray();

    expect($data['timeout'])->toBe(0);
});

it('validates position', function () {
    $toast = new Toast;
    $toast->success('Test')->position('invalid-pos');
})->throws(InvalidArgumentException::class);

it('allows valid positions', function () {
    foreach (FeedbackDefaults::positions() as $pos) {
        $toast = new Toast;
        $data = $toast->success('Test')->position($pos)->toArray();
        expect($data['position'])->toBe($pos);
    }
});

it('sets sole flag', function () {
    $toast = new Toast;
    $data = $toast->success('Test')->sole()->toArray();

    expect($data['sole'])->toBeTrue();
});

it('sets noGroup flag', function () {
    $toast = new Toast;
    $data = $toast->success('Test')->noGroup()->toArray();

    expect($data['noGroup'])->toBeTrue();
});

it('controls autoExpand', function () {
    $toast = new Toast;
    $data = $toast->success('Title', 'Desc')->expanded(false)->toArray();

    expect($data['autoExpand'])->toBeFalse();
});

it('adds actions to payload', function () {
    $toast = new Toast;
    $data = $toast->success('Deleted')
        ->action('Undo', 'restore', [1])
        ->action('View', 'show', [1])
        ->toArray();

    expect($data['actions'])->toHaveCount(2)
        ->and($data['actions'][0])->toBe(['label' => 'Undo', 'method' => 'restore', 'params' => [1]])
        ->and($data['actions'][1])->toBe(['label' => 'View', 'method' => 'show', 'params' => [1]]);
});

it('sets confirm/cancel options', function () {
    $toast = new Toast;
    $data = $toast->question('Discard?')
        ->confirm('Yes', 'discard', [1])
        ->cancel('No')
        ->toArray();

    expect($data['options']['confirm'])->toBe(['text' => 'Yes', 'method' => 'discard', 'params' => [1]])
        ->and($data['options']['cancel'])->toBe(['text' => 'No', 'method' => null, 'params' => []]);
});

it('adds hooks', function () {
    $toast = new Toast;
    $data = $toast->info('Processing')
        ->hook('close', 'onClosed')
        ->hook('timeout', 'onExpired', [42])
        ->toArray();

    expect($data['hooks']['close'])->toBe(['method' => 'onClosed', 'params' => []])
        ->and($data['hooks']['timeout'])->toBe(['method' => 'onExpired', 'params' => [42]]);
});

it('uses session flash when no component context', function () {
    $toast = new Toast;
    $toast->success('Stored')->send();

    expect(session('kore:toast'))->not->toBeNull()
        ->and(session('kore:toast')['title'])->toBe('Stored');
});

it('uses session flash when viaSession is set', function () {
    $toast = new Toast;
    $toast->success('Flash me')->viaSession()->send();

    expect(session('kore:toast'))->not->toBeNull()
        ->and(session('kore:toast')['title'])->toBe('Flash me');
});

it('returns toast ID from send', function () {
    $toast = new Toast;
    $id = $toast->success('Test')->send();

    expect($id)->toBeString()->not->toBeEmpty();
});

// --- Livewire context ---

beforeEach(function () {
    Livewire::component('demo-component', DemoComponent::class);
});

it('dispatches kore:toast event from Livewire component', function () {
    Livewire::test(DemoComponent::class)
        ->call('sendToast')
        ->assertDispatched('kore:toast');
});

it('includes component reference in payload when sent from Livewire', function () {
    $component = Livewire::test(DemoComponent::class);

    // Get the trait's toast() and build payload
    $toast = new Toast($component->instance());
    $data = $toast->success('Test')->toArray();

    expect($data['reference'])->not->toBeNull();
});
