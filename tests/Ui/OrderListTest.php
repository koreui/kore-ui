<?php

$items = "[['value' => 1, 'label' => 'Uno'], ['value' => 2, 'label' => 'Dos'], ['value' => 3, 'label' => 'Tres']]";

it('renders KoreOrderList Alpine data', function () use ($items) {
    $view = $this->blade("<x-kore::order-list name=\"order\" :items=\"{$items}\" />");
    $view->assertSee('KoreOrderList', false);
});

it('wraps the widget in wire:ignore', function () use ($items) {
    $view = $this->blade("<x-kore::order-list name=\"order\" :items=\"{$items}\" />");
    $view->assertSee('wire:ignore', false);
});

it('passes items to the JS config', function () use ($items) {
    $view = $this->blade("<x-kore::order-list name=\"order\" :items=\"{$items}\" />");
    $view->assertSee('Uno', false)
        ->assertSee('Dos', false);
});

it('emits x-sort for reordering', function () use ($items) {
    $view = $this->blade("<x-kore::order-list name=\"order\" :items=\"{$items}\" />");
    $view->assertSee('x-sort', false)
        ->assertSee('move($item, $position)', false);
});

it('renders up/down buttons', function () use ($items) {
    $view = $this->blade("<x-kore::order-list name=\"order\" :items=\"{$items}\" />");
    $view->assertSee('moveUp(index)', false)
        ->assertSee('moveDown(index)', false);
});

it('omits drag markup when not reorderable', function () use ($items) {
    $view = $this->blade("<x-kore::order-list name=\"order\" :items=\"{$items}\" :reorderable=\"false\" />");
    $view->assertDontSee('x-sort', false);
});

it('renders hidden input for wire:model', function () use ($items) {
    $view = $this->blade("<x-kore::order-list wire:model=\"order\" :items=\"{$items}\" />");
    $view->assertSee('wire:model="order"', false)
        ->assertSee('type="hidden"', false);
});

/**
 * Mismo arreglo que en el transfer: la raíz lleva `wire:ignore`, así que los
 * items tienen que llegar de fuera o el componente se queda con los de la
 * primera carga. Medido: el servidor añadía un elemento y la lista seguía
 * enseñando cuatro.
 */
it('saca los items a un nodo JSON fuera del wire:ignore', function () {
    $items = "[['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']]";
    $view = $this->blade("<x-kore::order-list name=\"orden\" :items=\"{$items}\" />");

    $view->assertSee('data-kore-order-list-items', false)
        ->assertSee("KoreOrderList({ itemsId: 'kore-order-list-items-", false);

    $html = $view->__toString();
    expect(strpos($html, 'data-kore-order-list-items'))->toBeLessThan(strpos($html, 'wire:ignore'));
});
