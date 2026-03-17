<?php

// ── Rendering básico ─────────────────────────────────────────

it('renders with label and name', function () {
    $view = $this->blade('
        <x-kore::datepicker label="Birth date" name="birth_date" />
    ');

    $view->assertSee('Birth date')
        ->assertSee('KoreDatePicker', false)
        ->assertSee('name="birth_date"', false);
});

it('renders placeholder', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" placeholder="Select a date" />
    ');

    $view->assertSee('Select a date');
});

it('renders calendar icon', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" />
    ');

    $view->assertSee('<svg', false);
});

it('forwards wire:model on hidden input', function () {
    $view = $this->blade('
        <x-kore::datepicker label="Date" wire:model="selectedDate" />
    ');

    $view->assertSee('wire:model="selectedDate"', false);
});

// ── Sizes ────────────────────────────────────────────────────

it('applies sm size classes', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" size="sm" />
    ');

    $view->assertSee('text-xs', false);
});

it('applies md size classes by default', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" />
    ');

    $view->assertSee('text-sm', false);
});

it('applies lg size classes', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" size="lg" />
    ');

    $view->assertSee('text-base', false);
});

// ── Errors ───────────────────────────────────────────────────

it('shows error from errors bag', function () {
    $this->withViewErrors(['date' => 'Date is required']);

    $view = $this->blade('
        <x-kore::datepicker name="date" />
    ');

    $view->assertSee('Date is required')
        ->assertSee('border-kore-destructive', false);
});

it('shows manual error via prop', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" error="Invalid date" />
    ');

    $view->assertSee('Invalid date')
        ->assertSee('border-kore-destructive', false);
});

it('hides error with show-error false', function () {
    $this->withViewErrors(['date' => 'Date is required']);

    $view = $this->blade('
        <x-kore::datepicker name="date" :show-error="false" />
    ');

    $view->assertDontSee('Date is required');
});

// ── Estados ──────────────────────────────────────────────────

it('renders disabled state', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" disabled />
    ');

    $view->assertSee('disabled', false)
        ->assertSee('opacity-50', false);
});

it('renders clearable button', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" clearable />
    ');

    $view->assertSee('clear()', false);
});

it('renders required indicator', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" label="Date" required />
    ');

    $view->assertSee('text-kore-destructive', false);
});

it('shows hint when no error', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" hint="Pick your preferred date" />
    ');

    $view->assertSee('Pick your preferred date');
});

// ── Modos ────────────────────────────────────────────────────

it('passes range mode to config', function () {
    $view = $this->blade('
        <x-kore::datepicker name="dates" mode="range" />
    ');

    $view->assertSee('mode', false)
        ->assertSee('range', false);
});

it('passes multiple mode to config', function () {
    $view = $this->blade('
        <x-kore::datepicker name="dates" mode="multiple" />
    ');

    $view->assertSee('mode', false)
        ->assertSee('multiple', false);
});

// ── Accesibilidad ────────────────────────────────────────────

it('has combobox role on trigger', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" />
    ');

    $view->assertSee('role="combobox"', false);
});

it('has dialog role on panel', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" />
    ');

    $view->assertSee('role="dialog"', false);
});

it('has grid role on calendar table', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" />
    ');

    $view->assertSee('role="grid"', false);
});

// ── Variantes ────────────────────────────────────────────────

it('renders inline without teleport', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" inline />
    ');

    $view->assertDontSee('x-teleport', false)
        ->assertSee('role="grid"', false);
});

it('renders time picker with withTime', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" with-time />
    ');

    $view->assertSee('incrementHour', false)
        ->assertSee('incrementMinute', false);
});

// ── Constraints ──────────────────────────────────────────────

it('passes min and max date to config', function () {
    $view = $this->blade('
        <x-kore::datepicker name="date" min-date="2026-01-01" max-date="2026-12-31" />
    ');

    $view->assertSee('minDate', false)
        ->assertSee('2026-01-01', false)
        ->assertSee('maxDate', false)
        ->assertSee('2026-12-31', false);
});
