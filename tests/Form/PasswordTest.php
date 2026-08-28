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

    $view->assertSee('&quot;minLength&quot;:12', false);
});

/**
 * Los textos del medidor de fuerza estaban escritos en inglés dentro de
 * `resources/js/form/password.js` —«Weak», «One uppercase letter»—, donde no
 * llegaba ni publicar las vistas: había que recompilar el bundle. Ahora viajan
 * desde `kore-ui.form.translations` dentro de la configuración del plugin.
 */
it('manda los textos del medidor al plugin', function () {
    $view = $this->blade('<x-kore::password label="Contraseña" name="password" :strength="true" />');

    $view->assertSee('Débil', false)
        ->assertSee('Regular', false)
        ->assertSee('Buena', false)
        ->assertSee('Fuerte', false)
        ->assertSee('Una letra mayúscula', false)
        ->assertSee('Un carácter especial', false);
});

it('interpola el mínimo en la regla de longitud', function () {
    $this->blade('<x-kore::password label="Contraseña" name="password" :strength="true" :min-length="12" />')
        ->assertSee('Al menos 12 caracteres', false)
        ->assertDontSee(':min', false);
});

it('los textos del medidor se cambian por configuración', function () {
    config()->set('kore-ui.form.translations.password_weak', 'Weak');
    config()->set('kore-ui.form.translations.password_rule_number', 'One number');

    $this->blade('<x-kore::password label="Password" name="password" :strength="true" />')
        ->assertSee('Weak', false)
        ->assertSee('One number', false);
});
