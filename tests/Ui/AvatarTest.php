<?php

it('renders with image', function () {
    $view = $this->blade('<x-kore::avatar src="/avatar.jpg" name="John" />');
    $view->assertSee('src="/avatar.jpg"', false)
        ->assertSee('alt="John"', false);
});

it('renders initials from name', function () {
    $view = $this->blade('<x-kore::avatar name="John Doe" />');
    $view->assertSee('JD');
});

it('renders single initial for single name', function () {
    $view = $this->blade('<x-kore::avatar name="John" />');
    $view->assertSee('J');
});

it('renders fallback icon when no src or name', function () {
    $view = $this->blade('<x-kore::avatar />');
    $view->assertSee('<svg', false);
});

it('renders custom icon', function () {
    $view = $this->blade('<x-kore::avatar icon="bot" />');
    $view->assertSee('<svg', false);
});

it('renders circle shape by default', function () {
    $view = $this->blade('<x-kore::avatar name="Test" />');
    $view->assertSee('rounded-full', false);
});

it('renders square shape', function () {
    $view = $this->blade('<x-kore::avatar name="Test" shape="square" />');
    $view->assertSee('rounded-kore-md', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::avatar name="Test" size="sm" />');
    $view->assertSee('size-8', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::avatar name="Test" size="lg" />');
    $view->assertSee('size-12', false);
});

it('renders online presence', function () {
    $view = $this->blade('<x-kore::avatar name="Test" presence="online" />');
    $view->assertSee('bg-kore-success', false);
});

it('renders offline presence', function () {
    $view = $this->blade('<x-kore::avatar name="Test" presence="offline" />');
    $view->assertSee('bg-kore-muted-fg', false);
});

it('renders avatar group', function () {
    $view = $this->blade('
        <x-kore::avatar-group>
            <x-kore::avatar name="A B" />
            <x-kore::avatar name="C D" />
        </x-kore::avatar-group>
    ');
    $view->assertSee('AB')
        ->assertSee('CD')
        ->assertSee('-space-x-', false);
});
