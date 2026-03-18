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

it('does not render strength meter by default', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" />');

    $view->assertDontSee('KorePassword', false);
});

it('renders strength meter when enabled', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :strength="true" />');

    $view->assertSee('levelLabel', false)
        ->assertSee('h-1.5', false);
});

it('renders KorePassword Alpine data when strength enabled', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :strength="true" />');

    $view->assertSee('KorePassword', false);
});

it('still renders toggle with strength enabled', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :strength="true" />');

    $view->assertSee('show', false);
});

it('renders rules checklist when showRules true', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :strength="true" />');

    $view->assertSee('rule.label', false);
});

it('hides rules checklist when showRules false', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :strength="true" :show-rules="false" />');

    $view->assertDontSee('rule.label', false);
});

it('forwards wire:model directly on input with strength', function () {
    $view = $this->blade('<x-kore::password label="Password" wire:model="password" :strength="true" />');

    $view->assertSee('wire:model="password"', false);
});

it('passes minLength config', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" :strength="true" :min-length="12" />');

    $view->assertSee('minLength: 12', false);
});
