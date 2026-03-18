<?php

// ── Rendering básico ─────────────────────────────────────────

it('renders with label and name', function () {
    $view = $this->blade('
        <x-kore::time-picker label="Start time" name="start_time" />
    ');

    $view->assertSee('Start time')
        ->assertSee('KoreTimePicker', false)
        ->assertSee('name="start_time"', false);
});

it('renders placeholder', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" placeholder="Select time" />
    ');

    $view->assertSee('Select time');
});

it('renders clock icon', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" />
    ');

    $view->assertSee('<svg', false);
});

it('forwards wire:model on hidden input', function () {
    $view = $this->blade('
        <x-kore::time-picker label="Time" wire:model="selectedTime" />
    ');

    $view->assertSee('wire:model="selectedTime"', false);
});

// ── Sizes ────────────────────────────────────────────────────

it('applies sm size classes', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" size="sm" />
    ');

    $view->assertSee('text-xs', false);
});

it('applies md size classes by default', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" />
    ');

    $view->assertSee('text-sm', false);
});

it('applies lg size classes', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" size="lg" />
    ');

    $view->assertSee('text-base', false);
});

// ── Errors ───────────────────────────────────────────────────

it('shows error from errors bag', function () {
    $this->withViewErrors(['time' => 'Time is required']);

    $view = $this->blade('
        <x-kore::time-picker name="time" />
    ');

    $view->assertSee('Time is required')
        ->assertSee('border-kore-destructive', false);
});

it('shows manual error via prop', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" error="Invalid time" />
    ');

    $view->assertSee('Invalid time')
        ->assertSee('border-kore-destructive', false);
});

it('hides error with show-error false', function () {
    $this->withViewErrors(['time' => 'Time is required']);

    $view = $this->blade('
        <x-kore::time-picker name="time" :show-error="false" />
    ');

    $view->assertDontSee('Time is required');
});

// ── Estados ──────────────────────────────────────────────────

it('renders disabled state', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" disabled />
    ');

    $view->assertSee('disabled', false)
        ->assertSee('opacity-50', false);
});

it('renders clearable button', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" clearable />
    ');

    $view->assertSee('clear()', false);
});

it('renders required indicator', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" label="Time" required />
    ');

    $view->assertSee('text-kore-destructive', false);
});

it('shows hint when no error', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" hint="Pick a time" />
    ');

    $view->assertSee('Pick a time');
});

// ── Config ──────────────────────────────────────────────────

it('passes 24h time format by default', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" />
    ');

    $view->assertSee('"timeFormat":"24"');
});

it('passes 12h time format', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" time-format="12" />
    ');

    $view->assertSee('"timeFormat":"12"');
});

it('renders AM/PM button for 12h format', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" time-format="12" />
    ');

    $view->assertSee('toggleAmPm()', false);
});

it('does not render AM/PM button for 24h format', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" time-format="24" />
    ');

    $view->assertDontSee('toggleAmPm()', false);
});

it('passes minute step to config', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" :minute-step="5" />
    ');

    $view->assertSee('"minuteStep":5');
});

// ── Accesibilidad ────────────────────────────────────────────

it('has combobox role on trigger', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" />
    ');

    $view->assertSee('role="combobox"', false);
});

it('has dialog role on panel', function () {
    $view = $this->blade('
        <x-kore::time-picker name="time" />
    ');

    $view->assertSee('role="dialog"', false);
});
