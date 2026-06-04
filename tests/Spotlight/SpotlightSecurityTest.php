<?php

use Illuminate\Support\Facades\Http;
use KoreUi\Spotlight\SpotlightItem;
use KoreUi\Spotlight\SpotlightManager;
use KoreUi\Spotlight\SpotlightResult;
use KoreUi\Tests\Spotlight\Fixtures\DemoProvider;
use Livewire\Livewire;

beforeEach(function () {
    config(['kore-ui.spotlight.providers' => []]);
});

// S1 — $providers is public input. Without #[Locked] the client can swap it for
// any class name, which getItems()/search() then resolve via app($class),
// instantiating an arbitrary class. Lock it and reject non-providers.

it('locks the providers property against client tampering', function () {
    $thrown = false;

    try {
        Livewire::test(SpotlightManager::class)->set('providers', [stdClass::class]);
    } catch (\Throwable $e) {
        $thrown = true;
    }

    expect($thrown)->toBeTrue();
});

it('does not use classes that are not SpotlightProviders', function () {
    $instance = new SpotlightManager;
    $instance->mount(providers: [stdClass::class, DemoProvider::class]);

    // Without the guard, sortBy(fn (SpotlightProvider $p) => ...) receives a
    // stdClass and throws a TypeError. The guard must skip it and still return
    // the DemoProvider items.
    $items = $instance->getItems();

    expect($items)->not->toBeEmpty();
});

// S2 — searchDependency() performs Http::get() against a URL taken from the
// client-supplied $dependency. It must not be allowed to reach internal/private
// hosts (SSRF → cloud metadata, localhost services, etc.).

it('blocks searchDependency from reaching a link-local metadata address', function () {
    Http::fake();
    $instance = new SpotlightManager;
    $instance->mount();

    $instance->searchDependency('cmd', ['searchUrl' => 'http://169.254.169.254/latest/meta-data/'], 'q');

    Http::assertNothingSent();
});

it('blocks searchDependency from reaching a private/loopback host', function () {
    Http::fake();
    $instance = new SpotlightManager;
    $instance->mount();

    $instance->searchDependency('cmd', ['searchUrl' => 'http://127.0.0.1:8080/admin'], 'q');
    $instance->searchDependency('cmd', ['searchUrl' => 'http://10.0.0.5/internal'], 'q');

    Http::assertNothingSent();
});

it('allows searchDependency to reach a public host', function () {
    Http::fake(['*' => Http::response([])]);
    $instance = new SpotlightManager;
    $instance->mount();

    $instance->searchDependency('cmd', ['searchUrl' => 'http://8.8.8.8/api'], 'q');

    Http::assertSentCount(1);
});

// S4 — 'url' action targets navigate client-side (window.location.href). A
// javascript:/data: scheme would execute script. Neutralize at the server,
// both for author-defined items and untrusted remote results.

it('neutralizes a javascript: url action target on an item', function () {
    $item = SpotlightItem::make('Evil')->url('javascript:alert(document.cookie)');

    expect($item->toArray()['actionTarget'])->toBeNull();
});

it('keeps a safe http(s) url action target on an item', function () {
    $item = SpotlightItem::make('Docs')->url('https://example.com/docs');

    expect($item->toArray()['actionTarget'])->toBe('https://example.com/docs');
});

it('neutralizes a dangerous url coming from a remote SpotlightResult', function () {
    $result = SpotlightResult::fromArray([
        'name'         => 'Evil',
        'actionType'   => 'url',
        'actionTarget' => 'javascript:alert(1)',
    ]);

    expect($result->toArray()['actionTarget'])->toBeNull();
});

it('does not touch a route action target (not a url)', function () {
    $item = SpotlightItem::make('Home')->route('home');

    expect($item->toArray()['actionTarget'])->toBe('home');
});
