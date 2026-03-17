<?php

it('renders correct number of inputs', function () {
    $view = $this->blade('<x-kore::input-otp label="Code" name="code" :length="6" />');

    $view->assertSee('Code')
        ->assertSee('KoreInputOtp', false)
        ->assertSee('maxlength="1"', false);

    // Should have 6 digit inputs (digit0 through digit5)
    $html = (string) $view;
    expect(substr_count($html, 'x-ref="digit'))->toBe(6);
});

it('renders 4 inputs when length is 4', function () {
    $view = $this->blade('<x-kore::input-otp label="PIN" name="pin" :length="4" />');

    $html = (string) $view;
    expect(substr_count($html, 'x-ref="digit'))->toBe(4);
});

it('renders numeric inputmode', function () {
    $view = $this->blade('<x-kore::input-otp label="PIN" name="pin" :length="4" numeric />');

    $view->assertSee('inputmode="numeric"', false);
});

it('renders password type when masked', function () {
    $view = $this->blade('<x-kore::input-otp label="PIN" name="pin" :length="4" masked />');

    $view->assertSee('type="password"', false);
});

it('renders separator at correct position', function () {
    $view = $this->blade('<x-kore::input-otp label="Code" name="code" :length="6" :separator-after="3" />');

    $view->assertSee('&ndash;', false);
});

it('forwards wire:model on hidden input', function () {
    $view = $this->blade('<x-kore::input-otp label="Code" :length="6" wire:model="code" />');

    $view->assertSee('wire:model="code"', false);
});
