<?php

it('renders with label and value', function () {
    $view = $this->blade('<x-kore::stats label="Revenue" :value="1234" />');
    $view->assertSee('Revenue')
        ->assertSee('1234');
});

it('renders default variant', function () {
    $view = $this->blade('<x-kore::stats label="Users" :value="500" />');
    $view->assertSee('text-3xl', false);
});

it('renders compact variant', function () {
    $view = $this->blade('<x-kore::stats label="Users" :value="500" variant="compact" />');
    $view->assertSee('text-xl', false);
});

it('renders with icon', function () {
    $view = $this->blade('<x-kore::stats label="Users" :value="100" icon="users" />');
    $view->assertSee('<svg', false);
});

it('renders as link when href is provided', function () {
    $view = $this->blade('<x-kore::stats label="Users" :value="100" href="/users" />');
    $view->assertSee('href="/users"', false)
        ->assertSee('hover:shadow-md', false);
});

it('renders trend up', function () {
    $view = $this->blade('<x-kore::stats label="Sales" :value="150" :previousValue="100" />');
    $view->assertSee('text-kore-success', false)
        ->assertSee('50%');
});

it('renders trend down', function () {
    $view = $this->blade('<x-kore::stats label="Sales" :value="80" :previousValue="100" />');
    $view->assertSee('text-kore-destructive', false)
        ->assertSee('20%');
});

it('renders without trend when none specified', function () {
    $view = $this->blade('<x-kore::stats label="Total" :value="100" trend="none" />');
    $view->assertDontSee('trending-up', false)
        ->assertDontSee('trending-down', false);
});

it('renders Alpine data for animation', function () {
    $view = $this->blade('<x-kore::stats label="Count" :value="500" />');
    $view->assertSee('KoreStats', false);
});

it('renders slot content', function () {
    $view = $this->blade('<x-kore::stats label="Revenue" :value="1000"><span>vs last month</span></x-kore::stats>');
    $view->assertSee('vs last month');
});
