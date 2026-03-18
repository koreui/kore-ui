<?php

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::rating label="Score" name="score" />');

    $view->assertSee('Score')
        ->assertSee('name="score"', false);
});

it('renders correct number of stars (default 5)', function () {
    $view = $this->blade('<x-kore::rating name="rating" />');

    // 5 stars = buttons with rate(1) through rate(5)
    $view->assertSee('rate(5)', false);
    $view->assertDontSee('rate(6)', false);
});

it('renders custom star count', function () {
    $view = $this->blade('<x-kore::rating name="rating" :stars="10" />');

    $view->assertSee('rate(10)', false);
    $view->assertDontSee('rate(11)', false);
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::rating name="rating" size="sm" />');

    $view->assertSee('size-4', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::rating name="rating" size="lg" />');

    $view->assertSee('size-7', false);
});

it('renders readonly state', function () {
    $view = $this->blade('<x-kore::rating name="rating" readonly />');

    $view->assertSee('pointer-events-none', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::rating name="rating" disabled />');

    $view->assertSee('opacity-50', false);
});

it('forwards wire:model on hidden input', function () {
    $view = $this->blade('<x-kore::rating wire:model="rating" />');

    $view->assertSee('wire:model="rating"', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['rating' => 'Please select a rating.']);

    $view = $this->blade('<x-kore::rating label="Rating" name="rating" />');

    $view->assertSee('Please select a rating.')
        ->assertSee('role="alert"', false);
});

it('shows manual error prop', function () {
    $view = $this->blade('<x-kore::rating label="Rating" name="rating" error="Invalid rating" />');

    $view->assertSee('Invalid rating')
        ->assertSee('role="alert"', false);
});

it('renders required indicator', function () {
    $view = $this->blade('<x-kore::rating label="Rating" name="rating" required />');

    $view->assertSee('*');
});

it('renders KoreRating Alpine data', function () {
    $view = $this->blade('<x-kore::rating name="rating" />');

    $view->assertSee('KoreRating', false);
});

it('passes allowHalf config', function () {
    $view = $this->blade('<x-kore::rating name="rating" allow-half />');

    $view->assertSee('allowHalf', false);
});

it('shows hint when no error', function () {
    $view = $this->blade('<x-kore::rating label="Rating" name="rating" hint="Select a rating" />');

    $view->assertSee('Select a rating');
});
