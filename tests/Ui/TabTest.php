<?php

it('renders tab container', function () {
    $view = $this->blade('
        <x-kore::tab>
            <x-kore::tab.item id="t1" label="Tab 1">Content 1</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('Content 1');
});

it('renders with x-data KoreTab', function () {
    $view = $this->blade('
        <x-kore::tab>
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('KoreTab', false);
});

it('renders tablist role', function () {
    $view = $this->blade('
        <x-kore::tab>
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('role="tablist"', false);
});

it('renders line variant', function () {
    $view = $this->blade('
        <x-kore::tab variant="line">
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('border-b', false);
});

it('renders pill variant', function () {
    $view = $this->blade('
        <x-kore::tab variant="pill">
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('bg-kore-muted', false)
        ->assertSee('rounded-kore-lg', false);
});

it('renders enclosed variant', function () {
    $view = $this->blade('
        <x-kore::tab variant="enclosed">
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('rounded-t-kore-lg', false);
});

it('renders tab item with tabpanel role', function () {
    $view = $this->blade('<x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>');
    $view->assertSee('role="tabpanel"', false);
});

it('renders tab item with aria-labelledby', function () {
    $view = $this->blade('<x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>');
    $view->assertSee('aria-labelledby', false);
});

it('renders selected prop passed to Alpine', function () {
    $view = $this->blade('
        <x-kore::tab selected="t1">
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee("selected: 't1'", false);
});

it('renders vertical orientation', function () {
    $view = $this->blade('
        <x-kore::tab orientation="vertical">
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('flex-row', false)
        ->assertSee('aria-orientation="vertical"', false);
});

it('renders scrollable', function () {
    $view = $this->blade('
        <x-kore::tab :scrollable="true">
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('overflow-x-auto', false);
});

it('renders item with lazy', function () {
    $view = $this->blade('<x-kore::tab.item id="t1" label="Tab 1" :lazy="true">Content</x-kore::tab.item>');
    $view->assertSee('x-if', false);
});

it('renders item with closeable', function () {
    $view = $this->blade('<x-kore::tab.item id="t1" label="Tab 1" :closeable="true">Content</x-kore::tab.item>');
    $view->assertSee("closeable: true", false);
});

it('renders item with badge', function () {
    $view = $this->blade('<x-kore::tab.item id="t1" label="Tab 1" :badge="5">Content</x-kore::tab.item>');
    $view->assertSee("badge: 5", false);
});

it('renders hidden input for wire model', function () {
    $view = $this->blade('
        <x-kore::tab>
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('type="hidden"', false);
});

it('renders horizontal orientation by default', function () {
    $view = $this->blade('
        <x-kore::tab>
            <x-kore::tab.item id="t1" label="Tab 1">Content</x-kore::tab.item>
        </x-kore::tab>
    ');
    $view->assertSee('aria-orientation="horizontal"', false);
});
