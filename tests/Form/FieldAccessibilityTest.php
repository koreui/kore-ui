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
