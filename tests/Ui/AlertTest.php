<?php

it('renders with title', function () {
    $view = $this->blade('<x-kore::alert title="Success!" />');
    $view->assertSee('Success!');
});

it('renders with description', function () {
    $view = $this->blade('<x-kore::alert description="Something happened" />');
    $view->assertSee('Something happened');
});

it('renders with slot content', function () {
    $view = $this->blade('<x-kore::alert>Custom message</x-kore::alert>');
    $view->assertSee('Custom message');
});

it('renders info type icon by default', function () {
    $view = $this->blade('<x-kore::alert title="Info" />');
    $view->assertSee('<svg', false);
});

it('renders success type icon', function () {
    $view = $this->blade('<x-kore::alert type="success" title="Done" />');
    $view->assertSee('<svg', false);
});

it('renders warning type icon', function () {
    $view = $this->blade('<x-kore::alert type="warning" title="Warning" />');
    $view->assertSee('<svg', false);
});

it('renders destructive type icon', function () {
    $view = $this->blade('<x-kore::alert type="destructive" title="Error" />');
    $view->assertSee('<svg', false);
});

it('has role alert', function () {
    $view = $this->blade('<x-kore::alert title="Test" />');
    $view->assertSee('role="alert"', false);
});

it('renders closeable button', function () {
    $view = $this->blade('<x-kore::alert title="Test" closeable />');
    $view->assertSee('x-on:click="show = false"', false);
});

it('renders soft variant by default', function () {
    $view = $this->blade('<x-kore::alert type="success" title="Done" />');
    $view->assertSee('bg-kore-success/10', false);
});

it('renders solid variant', function () {
    $view = $this->blade('<x-kore::alert type="success" variant="solid" title="Done" />');
    $view->assertSee('bg-kore-success', false)
        ->assertSee('text-kore-success-fg', false);
});

it('hides icon when show-icon is false', function () {
    $view = $this->blade('<x-kore::alert title="Test" :show-icon="false" />');
    $view->assertDontSee('size-5', false);
});
