<?php

it('renders dropdown component', function () {
    $view = $this->blade('
        <x-kore::dropdown>
            <x-slot:trigger>
                <button>Open</button>
            </x-slot:trigger>
            <x-kore::dropdown.item label="Option 1" />
        </x-kore::dropdown>
    ');
    $view->assertSee('Open')
        ->assertSee('KoreDropdown', false);
});

it('renders dropdown items', function () {
    $view = $this->blade('<x-kore::dropdown.item label="Edit" />');
    $view->assertSee('Edit')
        ->assertSee('role="menuitem"', false);
});

it('renders item with icon', function () {
    $view = $this->blade('<x-kore::dropdown.item label="Edit" icon="pencil" />');
    $view->assertSee('<svg', false);
});

it('renders item as link', function () {
    $view = $this->blade('<x-kore::dropdown.item label="Go" href="/test" />');
    $view->assertSee('<a', false)
        ->assertSee('href="/test"', false);
});

it('renders disabled item', function () {
    $view = $this->blade('<x-kore::dropdown.item label="Disabled" :disabled="true" />');
    $view->assertSee('opacity-50', false);
});

it('renders separator', function () {
    $view = $this->blade('<x-kore::dropdown.separator />');
    $view->assertSee('border-t', false);
});

it('renders header', function () {
    $view = $this->blade('<x-kore::dropdown.header label="Actions" />');
    $view->assertSee('Actions')
        ->assertSee('uppercase', false);
});

it('renders item with description', function () {
    $view = $this->blade('<x-kore::dropdown.item label="Export" description="Download as CSV" />');
    $view->assertSee('Export')
        ->assertSee('Download as CSV');
});

it('has menu role', function () {
    $view = $this->blade('
        <x-kore::dropdown>
            <x-slot:trigger><button>Open</button></x-slot:trigger>
            Content
        </x-kore::dropdown>
    ');
    $view->assertSee('role="menu"', false);
});

it('renders with teleport', function () {
    $view = $this->blade('
        <x-kore::dropdown>
            <x-slot:trigger><button>Open</button></x-slot:trigger>
            Content
        </x-kore::dropdown>
    ');
    $view->assertSee('x-teleport="body"', false);
});
