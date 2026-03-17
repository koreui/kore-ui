<?php

it('renders wrapper with label', function () {
    $view = $this->blade('
        <x-kore::float-label label="Email">
            <input type="text" placeholder=" " />
        </x-kore::float-label>
    ');

    $view->assertSee('Email')
        ->assertSee('kore-float-label', false);
});

it('renders slot content', function () {
    $view = $this->blade('
        <x-kore::float-label label="Name">
            <input type="text" id="custom-input" placeholder=" " />
        </x-kore::float-label>
    ');

    $view->assertSee('id="custom-input"', false);
});

it('renders over variant by default', function () {
    $view = $this->blade('
        <x-kore::float-label label="Name">
            <input type="text" placeholder=" " />
        </x-kore::float-label>
    ');

    $view->assertSee('-translate-y-1/2', false);
});

it('renders in variant', function () {
    $view = $this->blade('
        <x-kore::float-label label="Name" variant="in">
            <input type="text" placeholder=" " />
        </x-kore::float-label>
    ');

    $view->assertSee('top-2.5', false);
});

it('renders on variant', function () {
    $view = $this->blade('
        <x-kore::float-label label="Name" variant="on">
            <input type="text" placeholder=" " />
        </x-kore::float-label>
    ');

    $view->assertSee('-top-2.5', false);
});
