<?php

it('renders with password type', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" />');

    $view->assertSee('Password')
        ->assertSee("x-bind:type=\"show ? 'text' : 'password'\"", false);
});

it('renders toggle button by default', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" />');

    $view->assertSee('x-on:click="show = !show"', false);
});

it('hides toggle when toggleable is false', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :toggleable="false" />');

    $view->assertDontSee('x-on:click="show = !show"', false);
});

it('forwards wire:model', function () {
    $view = $this->blade('<x-kore::password label="Password" wire:model="password" />');

    $view->assertSee('wire:model="password"', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['password' => 'Password is too short']);

    $view = $this->blade('<x-kore::password label="Password" name="password" />');

    $view->assertSee('Password is too short')
        ->assertSee('border-kore-destructive', false);
});
