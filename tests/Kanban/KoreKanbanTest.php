<?php

use KoreUi\Tests\Kanban\Fixtures\TestBoard;
use Livewire\Livewire;

it('renders the columns', function () {
    Livewire::test(TestBoard::class)
        ->assertSee('Por hacer')
        ->assertSee('En curso')
        ->assertSee('Hecho');
});

it('renders the cards in their columns', function () {
    Livewire::test(TestBoard::class)
        ->assertSee('Tarea 1')
        ->assertSee('Tarea 3');
});

it('renders a sortable group per column', function () {
    Livewire::test(TestBoard::class)
        ->assertSeeHtml('x-sort:group="kanban"');
});

it('wires the move handler with the destination column', function () {
    Livewire::test(TestBoard::class)
        ->assertSeeHtml("\$wire.moveCard(\$item, \$position, 'done')");
});

it('moves a card to another column and persists it', function () {
    $component = Livewire::test(TestBoard::class)
        ->call('moveCard', 1, 0, 'done');

    $card = collect($component->get('items'))->firstWhere('id', 1);

    expect($card['column'])->toBe('done');
});
