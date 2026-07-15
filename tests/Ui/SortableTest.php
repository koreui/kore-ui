<?php

it('emits wire:sort with the handler in server mode', function () {
    $view = $this->blade('<x-kore::sortable handler="reorder"><div>x</div></x-kore::sortable>');
    $view->assertSee('wire:sort="reorder"', false);
});

it('emits the animation config', function () {
    $view = $this->blade('<x-kore::sortable handler="reorder"><div>x</div></x-kore::sortable>');
    $view->assertSee('wire:sort:config', false)
        ->assertSee('animation: 150', false);
});

it('emits x-sort in client mode', function () {
    $view = $this->blade('<x-kore::sortable mode="client"><div>x</div></x-kore::sortable>');
    $view->assertSee('x-sort', false)
        ->assertDontSee('wire:sort', false);
});

it('emits the group for cross-list dragging', function () {
    $view = $this->blade('<x-kore::sortable handler="reorder" group="tasks"><div>x</div></x-kore::sortable>');
    $view->assertSee('wire:sort:group="tasks"', false);
});

it('renders a custom tag', function () {
    $view = $this->blade('<x-kore::sortable tag="ul" handler="reorder"><li>x</li></x-kore::sortable>');
    $view->assertSee('<ul', false);
});

it('item emits wire:sort:item and a stable wire:key', function () {
    $view = $this->blade('<x-kore::sortable handler="reorder"><x-kore::sortable.item id="42">Row</x-kore::sortable.item></x-kore::sortable>');
    $view->assertSee('wire:sort:item="42"', false)
        ->assertSee('wire:key="kore-sortable-42"', false)
        ->assertSee('Row');
});

it('item renders a drag handle when handle is enabled', function () {
    $view = $this->blade('<x-kore::sortable handler="reorder" :handle="true"><x-kore::sortable.item id="1">Row</x-kore::sortable.item></x-kore::sortable>');
    $view->assertSee('wire:sort:handle', false)
        ->assertSee('<svg', false);
});

it('item inherits client mode from the container', function () {
    $view = $this->blade('<x-kore::sortable mode="client"><x-kore::sortable.item id="1">Row</x-kore::sortable.item></x-kore::sortable>');
    $view->assertSee('x-sort:item="1"', false);
});
