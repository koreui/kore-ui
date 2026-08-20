<?php

/**
 * Que la etiqueta apunte a un campo que existe y que se pueda enfocar.
 *
 * `<label for>` solo vale contra un control de formulario. Apuntarlo a un input
 * oculto —o a un `<div role="radiogroup">`, o a un id que no existe— deja la
 * etiqueta huérfana: pulsarla no enfoca nada y el campo se anuncia sin nombre.
 */
it('la etiqueta del modo moneda apunta al campo visible, no al oculto', function () {
    // El id vivía en el input oculto que lleva el `wire:model`. El campo que el
    // usuario ve y escribe no tenía ni id ni nombre.
    $html = (string) $this->blade('<x-kore::number label="Importe" name="importe" mode="currency" />');

    preg_match('/<label[^>]*for="([^"]+)"/', $html, $etiqueta);
    preg_match('/<input[^>]*id="'.preg_quote($etiqueta[1], '/').'"[^>]*>/', $html, $campo);

    expect($campo[0] ?? '')->not->toBeEmpty()
        ->and($campo[0])->not->toContain('type="hidden"');
});

it('el modo moneda emite los atributos del consumidor en el campo visible', function () {
    // En modo decimal se mergeaban; en moneda no se emitía ninguno, así que un
    // `placeholder` funcionaba o no según el modo, sin decir por qué.
    $this->blade('<x-kore::number label="Importe" name="i" mode="currency" placeholder="0,00" data-mio="1" />')
        ->assertSee('placeholder="0,00"', false)
        ->assertSee('data-mio="1"', false);
});

it('la etiqueta de tag-input apunta al campo donde se escribe', function () {
    $html = (string) $this->blade('<x-kore::tag-input label="Etiquetas" name="tags" />');

    preg_match('/<label[^>]*for="([^"]+)"/', $html, $etiqueta);
    preg_match('/<input[^>]*id="'.preg_quote($etiqueta[1], '/').'"[^>]*>/', $html, $campo);

    expect($campo[0] ?? '')->not->toBeEmpty()
        ->and($campo[0])->not->toContain('type="hidden"');
});

it('la etiqueta del OTP apunta a la primera casilla', function () {
    $html = (string) $this->blade('<x-kore::input-otp label="Código" name="c" :length="4" />');

    preg_match('/<label[^>]*for="([^"]+)"/', $html, $etiqueta);
    preg_match('/<input[^>]*id="'.preg_quote($etiqueta[1], '/').'"[^>]*>/', $html, $campo);

    expect($campo[0] ?? '')->not->toBeEmpty()
        ->and($campo[0])->toContain('x-ref="digit0"');
});

it('el grupo de radios se nombra con aria-labelledby y no con for', function () {
    // Un `<div role="radiogroup">` no es etiquetable: el `for` de la etiqueta
    // apuntaba a un id que no existía en ninguna parte y el grupo se anunciaba
    // sin nombre.
    $html = (string) $this->blade('
        <x-kore::radio-group label="Plan" name="plan">
            <x-kore::radio wire:model="plan" value="a" label="A" />
        </x-kore::radio-group>
    ');

    expect($html)->toContain('id="kore-plan-label"')
        ->and($html)->toContain('aria-labelledby="kore-plan-label"')
        ->and($html)->not->toContain('for="kore-plan"');
});

it('un grupo de radios sin name también tiene nombre', function () {
    // Es el caso del ejemplo de la documentación: el `wire:model` va en cada
    // radio y el grupo no lleva `name`. Sin id no había `aria-labelledby`.
    $html = (string) $this->blade('
        <x-kore::radio-group label="Plan">
            <x-kore::radio wire:model="plan" value="a" label="A" />
        </x-kore::radio-group>
    ');

    preg_match('/aria-labelledby="([^"]+)"/', $html, $ref);

    expect($ref[1] ?? '')->not->toBeEmpty()
        ->and($html)->toContain('id="'.$ref[1].'"');
});

it('el calendario empotrado se nombra como grupo, sin for huérfano', function () {
    // Empotrado no hay disparador, así que no existía ningún elemento con el id
    // del campo y el `for` de la etiqueta no apuntaba a nada.
    $html = (string) $this->blade('<x-kore::datepicker label="Fecha" name="f" inline />');

    expect($html)->toContain('role="group"')
        ->and($html)->toContain('aria-labelledby="kore-f-label"')
        ->and($html)->not->toContain('for="kore-f"');
});

it('field deja de emitir for cuando lo que envuelve no es un control', function () {
    $conControl = (string) $this->blade('<x-kore::field label="A" field-id="x"><input id="x" /></x-kore::field>');
    $sinControl = (string) $this->blade('<x-kore::field label="A" field-id="x" :labelable="false"><div id="x"></div></x-kore::field>');

    expect($conControl)->toContain('for="x"')
        ->and($sinControl)->not->toContain('for="x"')
        ->and($sinControl)->toContain('id="x-label"');
});
