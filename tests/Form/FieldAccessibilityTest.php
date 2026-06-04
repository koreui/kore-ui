<?php

// WCAG 3.3.1 / 4.1.2 — form controls must associate their hint/error with the
// control via aria-describedby, and signal validity via aria-invalid. The shared
// field template exposes deterministic ids ({fieldId}-error / {fieldId}-hint)
// and each control references them.

it('input exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" error="Invalid email" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-email-error"', false)
        ->assertSee('id="kore-email-error"', false);
});

it('input links the hint via aria-describedby when there is no error', function () {
    $view = $this->blade('<x-kore::input label="Email" name="email" hint="We never share it" />');

    $view->assertSee('aria-describedby="kore-email-hint"', false)
        ->assertSee('id="kore-email-hint"', false)
        ->assertDontSee('aria-invalid="true"', false);
});

it('input has no aria-describedby or aria-invalid without a hint or error', function () {
    $view = $this->blade('<x-kore::input label="Name" name="name" />');

    $view->assertDontSee('aria-describedby', false)
        ->assertDontSee('aria-invalid', false);
});

it('textarea exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" error="Too long" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-bio-error"', false)
        ->assertSee('id="kore-bio-error"', false);
});

it('textarea links the hint via aria-describedby even with a maxLength counter', function () {
    $view = $this->blade('<x-kore::textarea label="Bio" name="bio" hint="Keep it short" :max-length="200" />');

    $view->assertSee('aria-describedby="kore-bio-hint"', false)
        ->assertSee('id="kore-bio-hint"', false);
});

it('password exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" error="Too weak" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-password-error"', false)
        ->assertSee('id="kore-password-error"', false);
});

it('password links the hint via aria-describedby with the strength meter enabled', function () {
    $view = $this->blade('<x-kore::password label="Password" name="password" hint="Min 8 chars" :strength="true" />');

    $view->assertSee('aria-describedby="kore-password-hint"', false)
        ->assertSee('id="kore-password-hint"', false);
});

it('number exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::number label="Age" name="age" error="Out of range" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-age-error"', false)
        ->assertSee('id="kore-age-error"', false);
});

it('number currency mode links the error via aria-describedby on the visible input', function () {
    $view = $this->blade('<x-kore::number label="Price" name="price" mode="currency" error="Invalid" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-price-error"', false);
});

it('maskable exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::maskable label="Phone" name="phone" mask="(###) ###-####" error="Invalid" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-phone-error"', false)
        ->assertSee('id="kore-phone-error"', false);
});

it('checkbox exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::checkbox label="Accept" name="terms" error="Required" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-terms-error"', false)
        ->assertSee('id="kore-terms-error"', false);
});

it('checkbox links its description via aria-describedby when there is no error', function () {
    $view = $this->blade('<x-kore::checkbox label="Accept" name="terms" description="You agree to the rules" />');

    $view->assertSee('aria-describedby="kore-terms-description"', false)
        ->assertSee('id="kore-terms-description"', false);
});

it('toggle exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::toggle label="Notify" name="notify" error="Required" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-notify-error"', false)
        ->assertSee('id="kore-notify-error"', false);
});

it('radio exposes aria-invalid and links the error via aria-describedby', function () {
    $view = $this->blade('<x-kore::radio label="Option A" name="choice" value="a" error="Required" />');

    $view->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-choice-a-error"', false)
        ->assertSee('id="kore-choice-a-error"', false);
});

it('radio-group associates its error with the radiogroup via aria-describedby', function () {
    $view = $this->blade('<x-kore::radio-group label="Pick one" name="plan" error="Choose a plan"><span>options</span></x-kore::radio-group>');

    $view->assertSee('role="radiogroup"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="kore-plan-error"', false)
        ->assertSee('id="kore-plan-error"', false);
});
