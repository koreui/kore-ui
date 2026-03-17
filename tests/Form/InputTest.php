<?php

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" />');

    $view->assertSee('Email')
        ->assertSee('name="email"', false);
});

it('renders correct input type', function () {
    $view = $this->blade('<x-kore::input type="email" name="email" />');

    $view->assertSee('type="email"', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['email' => 'The email is required.']);

    $view = $this->blade('<x-kore::input label="Email" name="email" />');

    $view->assertSee('The email is required.')
        ->assertSee('role="alert"', false);
});

it('shows manual error prop', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" error="Invalid email" />');

    $view->assertSee('Invalid email')
        ->assertSee('role="alert"', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::input label="Name" name="name" size="sm" />');

    $view->assertSee('text-xs', false);
});

it('renders medium size by default', function () {
    $view = $this->blade('<x-kore::input label="Name" name="name" />');

    $view->assertSee('text-sm', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::input label="Name" name="name" size="lg" />');

    $view->assertSee('text-base', false);
});

it('renders left icon with padding', function () {
    $view = $this->blade('<x-kore::input label="Search" name="search" icon="search" />');

    $view->assertSee('pl-10', false);
});

it('renders right icon', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" icon-right="check" />');

    $view->assertSee('pr-10', false);
});

it('renders prefix text', function () {
    $view = $this->blade('<x-kore::input label="URL" name="url" prefix="https://" />');

    $view->assertSee('https://');
});

it('renders suffix text', function () {
    $view = $this->blade('<x-kore::input label="Domain" name="domain" suffix=".com" />');

    $view->assertSee('.com');
});

it('renders clearable button', function () {
    $view = $this->blade('<x-kore::input label="Search" name="search" clearable />');

    $view->assertSee('x-data=', false)
        ->assertSee('x-ref="input"', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::input label="Name" name="name" disabled />');

    $view->assertSee('disabled', false);
});

it('forwards wire:model attribute', function () {
    $view = $this->blade('<x-kore::input label="Name" wire:model="name" />');

    $view->assertSee('wire:model="name"', false);
});

it('hides error when show-error is false', function () {
    $this->withViewErrors(['email' => 'The email is required.']);

    $view = $this->blade('<x-kore::input label="Email" name="email" :show-error="false" />');

    $view->assertDontSee('The email is required.');
});

it('shows hint when no error', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" hint="We will not share your email" />');

    $view->assertSee('We will not share your email');
});

it('hides hint when error present', function () {
    $this->withViewErrors(['email' => 'Required']);

    $view = $this->blade('<x-kore::input label="Email" name="email" hint="Hint text" />');

    $view->assertSee('Required')
        ->assertDontSee('Hint text');
});

it('renders error border class when error present', function () {
    $this->withViewErrors(['email' => 'Required']);

    $view = $this->blade('<x-kore::input label="Email" name="email" />');

    $view->assertSee('border-kore-destructive', false);
});

it('renders required indicator', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" required />');

    $view->assertSee('*')
        ->assertSee('required', false);
});
