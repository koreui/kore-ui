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

/**
 * Los items ya no viajan dentro del `x-data`.
 *
 * La raíz lleva `wire:ignore`, así que lo que entra por el `x-data` se queda con
 * lo de la primera carga: medido en un navegador, el servidor pasaba de cuatro
 * elementos a cinco y los dos paneles seguían enseñando cuatro para siempre.
 * Ahora van en un nodo JSON de FUERA, que Livewire sí actualiza al hacer morph.
 */
it('saca los items a un nodo JSON fuera del wire:ignore', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");

    $view->assertSee('<script type="application/json" id="kore-transfer-items-', false)
        ->assertSee('data-kore-transfer-items', false)
        ->assertSee("KoreTransfer({ itemsId: 'kore-transfer-items-", false);

    // El nodo JSON va ANTES del `wire:ignore`, o el morph tampoco lo tocaría.
    $html = $view->__toString();
    expect(strpos($html, 'data-kore-transfer-items'))->toBeLessThan(strpos($html, 'wire:ignore'));
});

/** Y no van también dentro: duplicarlos es peso de más en cada página. */
it('no manda los items dos veces', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");
    expect(substr_count($view->__toString(), 'Alpha'))->toBe(1);
});

/**
 * Las casillas y las cajas de búsqueda no tenían nombre: seis controles sin él
 * en una sola doble lista. El `placeholder` no vale, porque desaparece en cuanto
 * se escribe algo.
 */
it('nombra las casillas y las búsquedas', function () use ($items) {
    $view = $this->blade("<x-kore::transfer name=\"roles\" :items=\"{$items}\" />");

    $view->assertSee('aria-label="Buscar en Disponibles"', false)
        ->assertSee('aria-label="Buscar en Seleccionados"', false)
        ->assertSee(':aria-label="', false);
});
