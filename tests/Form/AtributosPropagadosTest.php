<?php

/**
 * Los atributos que se escriben en la etiqueta tienen que llegar al DOM.
 *
 * Diez componentes de formulario no volcaban `$attributes` en ninguna parte: un
 * `class`, un `data-*`, un `style`, un `aria-describedby` o un `x-on:` escrito
 * en la etiqueta se quedaba en el bag y no se emitía. No daba error ni aviso —
 * el atributo simplemente no existía—, así que la única forma de darse cuenta
 * era mirar el HTML resultante. `select` lo hacía solo en modo nativo, y
 * `radio-group` filtraba todo menos `class`.
 */
$componentes = [
    'datepicker'   => '<x-kore::datepicker label="F" class="mia" data-prueba="1" />',
    'time-picker'  => '<x-kore::time-picker label="H" class="mia" data-prueba="1" />',
    'color-picker' => '<x-kore::color-picker label="C" class="mia" data-prueba="1" />',
    'input-otp'    => '<x-kore::input-otp label="O" class="mia" data-prueba="1" :length="4" />',
    'tag-input'    => '<x-kore::tag-input label="T" class="mia" data-prueba="1" />',
    'key-value'    => '<x-kore::key-value label="K" class="mia" data-prueba="1" />',
    'upload'       => '<x-kore::upload label="U" class="mia" data-prueba="1" />',
    'rating'       => '<x-kore::rating label="R" class="mia" data-prueba="1" />',
    'maskable'     => '<x-kore::maskable label="M" class="mia" data-prueba="1" mask="###" />',
    'repeater'     => '<x-kore::repeater label="P" class="mia" data-prueba="1" :fields="[[\'key\' => \'a\']]" />',
    'select'       => '<x-kore::select label="S" class="mia" data-prueba="1" :options="[]" />',
    'radio-group'  => '<x-kore::radio-group label="G" class="mia" data-prueba="1"><x-kore::radio value="a" label="A" /></x-kore::radio-group>',
    // El modo doble de `range` no tiene UN control donde mergear —dos
    // deslizadores y un input oculto— y se quedó fuera del barrido: solo se miró
    // su modo simple, que sí mergea en el suyo.
    'range doble'  => '<x-kore::range label="R" class="mia" data-prueba="1" range />',
];

foreach ($componentes as $nombre => $plantilla) {
    it("propaga los atributos de la etiqueta en {$nombre}", function () use ($plantilla) {
        $this->blade($plantilla)
            ->assertSee('data-prueba="1"', false)
            ->assertSee('mia', false);
    });
}

it('suma la clase del consumidor a las del componente en vez de sustituirlas', function () {
    // `merge()` y no un `class=` a secas: si sustituyera, un `class="w-64"`
    // dejaría al componente sin ninguno de sus propios estilos.
    $html = (string) $this->blade('<x-kore::tag-input label="T" class="mia" />');

    preg_match('/class="([^"]*mia[^"]*)"/', $html, $m);

    expect($m[1] ?? '')->toContain('mia')
        ->and($m[1] ?? '')->toContain('rounded-kore-md');
});

it('no duplica el id al volcar los atributos en la raíz', function () {
    // El `id` del consumidor ya lo consume `$fieldId` sobre el control. Si
    // además se volcara en la raíz habría dos elementos con el mismo id, y
    // `<label for>` resolvería siempre al primero.
    $html = (string) $this->blade('<x-kore::datepicker label="F" id="propio" />');

    expect(substr_count($html, 'id="propio"'))->toBe(1);
});

it('no duplica el wire:model al volcar los atributos en la raíz', function () {
    // Vive en el input oculto. Repetirlo en la raíz serían dos enlaces al mismo
    // modelo, con dos escrituras por cada cambio.
    $html = (string) $this->blade('<x-kore::tag-input label="T" wire:model="etiquetas" />');

    expect(substr_count($html, 'wire:model="etiquetas"'))->toBe(1);
});
