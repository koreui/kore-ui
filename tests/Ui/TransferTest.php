<?php

$items = "[['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta'], ['value' => 'c', 'label' => 'Gamma']]";

it('renders KoreTransfer Alpine data', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");
    $view->assertSee('KoreTransfer', false);
});

it('wraps the widget in wire:ignore', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");
    $view->assertSee('wire:ignore', false);
});

it('passes items to the JS config', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");
    $view->assertSee('Alpha', false)
        ->assertSee('Beta', false);
});

it('renders both panels with custom titles', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" :titles=\"['Todos', 'Elegidos']\" />");
    $view->assertSee('Todos')
        ->assertSee('Elegidos');
});

it('renders the move buttons', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");
    $view->assertSee('moveToTarget()', false)
        ->assertSee('moveToSource()', false)
        ->assertSee('moveAllToTarget()', false)
        ->assertSee('moveAllToSource()', false);
});

it('renders search inputs when searchable', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");
    $view->assertSee('sourceSearch', false)
        ->assertSee('targetSearch', false);
});

it('hides search inputs when not searchable', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" :searchable=\"false\" />");
    $view->assertDontSee('sourceSearch', false);
});

it('renders hidden input for wire:model', function () use ($items) {
    $view = $this->blade("<x-kore::transfer wire:model=\"roles\" :items=\"{$items}\" />");
    $view->assertSee('wire:model="roles"', false)
        ->assertSee('type="hidden"', false);
});
