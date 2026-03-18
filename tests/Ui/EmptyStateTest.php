<?php

it('renders with title', function () {
    $view = $this->blade('<x-kore::empty-state title="No results" />');
    $view->assertSee('No results');
});

it('renders with description', function () {
    $view = $this->blade('<x-kore::empty-state title="Empty" description="Try adjusting your filters." />');
    $view->assertSee('Try adjusting your filters.');
});

it('renders with icon', function () {
    $view = $this->blade('<x-kore::empty-state title="Empty" icon="inbox" />');
    $view->assertSee('<svg', false);
});

it('renders with image', function () {
    $view = $this->blade('<x-kore::empty-state title="Empty" image="/img/empty.svg" />');
    $view->assertSee('src="/img/empty.svg"', false);
});

it('renders slot content', function () {
    $view = $this->blade('<x-kore::empty-state title="Empty"><p>Custom content</p></x-kore::empty-state>');
    $view->assertSee('Custom content');
});

it('renders action slot', function () {
    $view = $this->blade('
        <x-kore::empty-state title="Empty">
            <x-slot:action><button>Add item</button></x-slot:action>
        </x-kore::empty-state>
    ');
    $view->assertSee('Add item');
});
