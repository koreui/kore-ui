<?php

it('renders with label and name', function () {
    $view = $this->blade('<x-kore::maskable label="Phone" name="phone" mask="(##) ####-####" />');

    $view->assertSee('Phone')
        ->assertSee('name="phone"', false);
});

it('renders KoreMaskable Alpine data', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" />');

    $view->assertSee('KoreMaskable', false);
});

it('renders hidden input when emitFormatted is false', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" />');

    $view->assertSee('type="hidden"', false);
});

it('does not render hidden input when emitFormatted is true', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" emit-formatted />');

    $view->assertDontSee('type="hidden"', false);
});

it('forwards wire:model on hidden input when emitFormatted is false', function () {
    $view = $this->blade('<x-kore::maskable wire:model="phone" mask="(##) ####-####" />');

    $view->assertSee('wire:model="phone"', false);
});

it('forwards wire:model on visible input when emitFormatted is true', function () {
    $view = $this->blade('<x-kore::maskable wire:model="phone" mask="(##) ####-####" emit-formatted />');

    $view->assertSee('wire:model="phone"', false);
});

it('renders auto-generated placeholder from mask', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" />');

    $view->assertSee('(__) ____-____');
});

it('renders custom placeholder overriding auto-generated', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" placeholder="Enter phone" />');

    $view->assertSee('Enter phone')
        ->assertDontSee('(__) ____-____');
});

it('renders left icon', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" icon="phone" />');

    $view->assertSee('pl-10', false);
});

it('renders right icon', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" icon-right="check" />');

    $view->assertSee('pr-10', false);
});

it('renders clearable button', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" clearable />');

    $view->assertSee('clear()', false);
});

it('renders disabled state', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" disabled />');

    $view->assertSee('disabled', false);
});

it('renders readonly state', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" readonly />');

    $view->assertSee('readonly', false);
});

it('shows error from errors bag', function () {
    $this->withViewErrors(['phone' => 'Invalid phone number.']);

    $view = $this->blade('<x-kore::maskable label="Phone" name="phone" mask="(##) ####-####" />');

    $view->assertSee('Invalid phone number.')
        ->assertSee('role="alert"', false);
});

it('shows manual error prop', function () {
    $view = $this->blade('<x-kore::maskable label="Phone" name="phone" mask="(##) ####-####" error="Wrong format" />');

    $view->assertSee('Wrong format')
        ->assertSee('role="alert"', false);
});

it('renders required indicator', function () {
    $view = $this->blade('<x-kore::maskable label="Phone" name="phone" mask="(##) ####-####" required />');

    $view->assertSee('*');
});

it('renders small size', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" size="sm" />');

    $view->assertSee('text-xs', false);
});

it('renders large size', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" size="lg" />');

    $view->assertSee('text-base', false);
});

it('passes mask config', function () {
    $view = $this->blade('<x-kore::maskable name="phone" mask="(##) ####-####" />');

    $view->assertSee('masks', false);
});

it('passes dynamic masks array', function () {
    $view = $this->blade('<x-kore::maskable name="phone" :mask="[\'(##) ####-####\', \'(##) #####-####\']" />');

    $view->assertSee('masks', false);
});

it('shows hint when no error', function () {
    $view = $this->blade('<x-kore::maskable label="Phone" name="phone" mask="(##) ####-####" hint="Format: (XX) XXXX-XXXX" />');

    $view->assertSee('Format: (XX) XXXX-XXXX');
});

/**
 * Los tres ajustes de `kore-ui.form.maskable` no se aplicaban nunca.
 *
 * Las props traían su valor por defecto escrito en el `@props`
 * (`'slotChar' => '_'`), así que el `?? config(...)` de debajo no disparaba
 * jamás: el `??` solo mira si el valor es null. La documentación prometía tres
 * ajustes que no hacían nada, y no daban error — simplemente se ignoraban.
 */
it('respeta el carácter de hueco de la configuración', function () {
    config()->set('kore-ui.form.maskable.slot_char', '*');

    $this->blade('<x-kore::maskable label="Tel" name="t" mask="(##) ####" />')
        ->assertSee('(**) ****', false);
});

it('respeta emit_formatted y auto_clear de la configuración', function () {
    config()->set('kore-ui.form.maskable.emit_formatted', true);
    config()->set('kore-ui.form.maskable.auto_clear', true);

    $html = (string) $this->blade('<x-kore::maskable label="Tel" name="t" mask="(##) ####" />');

    expect($html)->toContain('emitFormatted')
        ->and($html)->toContain('autoClear');
});

it('la prop de la etiqueta gana a la configuración', function () {
    config()->set('kore-ui.form.maskable.slot_char', '*');

    $this->blade('<x-kore::maskable label="Tel" name="t" mask="(##) ####" slot-char="_" />')
        ->assertSee('(__) ____', false);
});
