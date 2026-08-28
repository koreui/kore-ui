<?php

/**
 * `readonly` en los componentes de formulario compuestos.
 *
 * La diferencia con `disabled` es el envío: un campo deshabilitado no viaja con
 * el formulario, uno de solo lectura sí. Antes de esto, once de estos doce
 * componentes ignoraban el atributo —no lo declaraban siquiera— y el `number` lo
 * pasaba al input pero dejaba vivas las flechas. Un formulario en modo consulta
 * no se podía montar sin deshabilitarlo, que es otra cosa.
 */

dataset('controles de edición', [
    // [etiqueta blade, marca que solo aparece cuando se puede editar]
    'number'      => ['<x-kore::number name="n" %s />', 'hover:bg-kore-muted'],
    'tag-input'   => ['<x-kore::tag-input name="n" clearable %s />', 'removeTag('],
    'key-value'   => ['<x-kore::key-value name="n" %s />', 'addPair()'],
    'input-otp'   => ['<x-kore::input-otp name="n" %s />', 'onInput('],
    'color-picker'=> ['<x-kore::color-picker name="n" %s />', 'toggle()'],
    'time-picker' => ['<x-kore::time-picker name="n" %s />', 'toggle()'],
    'datepicker'  => ['<x-kore::datepicker name="n" %s />', 'toggle()'],
    'select'      => ['<x-kore::select name="n" :options="[\'a\' => \'A\']" %s />', 'toggle()'],
    'upload'      => ['<x-kore::upload name="n" %s />', 'openFilePicker()'],
    'repeater'    => ['<x-kore::repeater name="n" :fields="[[\'key\' => \'a\']]" %s />', 'addRow()'],
    'transfer'    => ['<x-kore::transfer name="n" :items="[[\'value\' => 1, \'label\' => \'A\']]" %s />', 'toggleCheck('],
    'order-list'  => ['<x-kore::order-list name="n" :items="[[\'value\' => 1, \'label\' => \'A\']]" %s />', 'x-sort:handle'],
]);

it('deja de ofrecer edición cuando es readonly', function (string $plantilla, string $marca) {
    $this->blade(sprintf($plantilla, ''))->assertSee($marca, false);

    $this->blade(sprintf($plantilla, 'readonly'))->assertDontSee($marca, false);
})->with('controles de edición');

it('anuncia el estado a las tecnologías de asistencia', function (string $plantilla) {
    $vista = $this->blade(sprintf($plantilla, 'readonly'));

    // O el atributo nativo, que ya lo comunica por sí solo, o aria-readonly
    // donde el control no es un input de texto.
    expect(str_contains($vista->__toString(), 'readonly'))->toBeTrue();
})->with('controles de edición');

it('mantiene el valor enviable, que es lo que lo separa de disabled', function (string $plantilla) {
    $html = $this->blade(sprintf($plantilla, 'readonly'))->__toString();

    expect($html)->toContain('name="n"')
        ->and($html)->not->toContain('disabled="disabled" name="n"');
})->with('controles de edición');

it('apaga las flechas del number sin atenuar el grupo entero', function () {
    // Con `disabled` la opacidad la pone el contenedor sobre todo el conjunto;
    // encadenar las dos dejaría los botones al 25%.
    $vista = $this->blade('<x-kore::number name="n" readonly />')->__toString();

    // Los botones siguen ahí —quitarlos movería el ancho del campo entre el modo
    // edición y el de consulta—, pero apagados y atenuados por su cuenta.
    expect($vista)->toContain('aria-label="Sumar"')
        ->and($vista)->toContain('opacity-50')
        ->and(substr_count($vista, 'disabled'))->toBeGreaterThanOrEqual(2);

    expect($this->blade('<x-kore::number name="n" />')->__toString())
        ->not->toContain('opacity-50');
});

it('no abre el panel del datepicker inline en solo lectura', function () {
    $this->blade('<x-kore::datepicker name="n" inline readonly />')
        ->assertSee('pointer-events-none', false);
});

it('sigue permitiendo buscar en el transfer, porque buscar no es editar', function () {
    $this->blade('<x-kore::transfer name="n" :items="[[\'value\' => 1, \'label\' => \'A\']]" searchable readonly />')
        ->assertSee('sourceSearch', false);
});
