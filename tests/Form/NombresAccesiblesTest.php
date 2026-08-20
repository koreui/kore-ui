<?php

/**
 * Nombre accesible de los controles que solo llevan un icono.
 *
 * Un botón cuyo contenido es una «×» no dice nada por sí mismo: un lector de
 * pantalla anuncia «botón» y ya. `title` no vale como sustituto —no se expone de
 * forma fiable en navegación táctil ni en todos los lectores—, así que estos
 * controles necesitan `aria-label`.
 *
 * Los textos salen de `kore-ui.form.translations` para que se puedan traducir
 * sin publicar las vistas.
 */
it('nombra los dos botones de paso de number', function () {
    $this->blade('<x-kore::number label="Cantidad" name="n" />')
        ->assertSee('aria-label="Sumar"', false)
        ->assertSee('aria-label="Restar"', false);
});

it('nombra el botón de limpiar de input', function () {
    $this->blade('<x-kore::input label="Buscar" name="q" clearable />')
        ->assertSee('aria-label="Limpiar"', false);
});

it('nombra el botón de limpiar de select y su buscador', function () {
    $this->blade('<x-kore::select label="País" name="p" clearable searchable :options="[\'es\' => \'España\']" />')
        ->assertSee('aria-label="Limpiar"', false)
        ->assertSee('aria-label="Buscar"', false);
});

it('nombra el botón de limpiar del datepicker', function () {
    $this->blade('<x-kore::datepicker label="Fecha" name="f" clearable />')
        ->assertSee('aria-label="Limpiar"', false);
});

it('nombra las flechas de mes del calendario', function () {
    $this->blade('<x-kore::datepicker label="Fecha" name="f" :months="2" />')
        ->assertSee('aria-label="Mes anterior"', false)
        ->assertSee('aria-label="Mes siguiente"', false);
});

it('nombra las cuatro flechas del reloj', function () {
    $this->blade('<x-kore::time-picker label="Hora" name="h" />')
        ->assertSee('aria-label="Subir la hora"', false)
        ->assertSee('aria-label="Bajar la hora"', false)
        ->assertSee('aria-label="Subir los minutos"', false)
        ->assertSee('aria-label="Bajar los minutos"', false);
});

it('nombra cada casilla del OTP', function () {
    $this->blade('<x-kore::input-otp label="Código" name="c" :length="3" />')
        ->assertSee('aria-label="Dígito 1"', false)
        ->assertSee('aria-label="Dígito 2"', false)
        ->assertSee('aria-label="Dígito 3"', false);
});

it('nombra las muestras del color-picker con su color', function () {
    $this->blade('<x-kore::color-picker label="Color" name="c" :colors="[\'#ef4444\']" inline />')
        ->assertSee('aria-label="Elegir color #ef4444"', false)
        ->assertSee('aria-label="Color personalizado"', false);
});

it('nombra el asa de arrastre y los campos de key-value', function () {
    $this->blade('<x-kore::key-value label="Meta" name="m" reorderable />')
        ->assertSee('aria-label="Arrastrar para reordenar"', false)
        ->assertSee('Clave', false)
        ->assertSee('Valor', false);
});

it('nombra los dos deslizadores de un range doble, y distinto', function () {
    $html = (string) $this->blade('<x-kore::range label="Precio" name="p" range />');

    expect($html)->toContain('aria-label="Precio — mínimo"')
        ->and($html)->toContain('aria-label="Precio — máximo"');
});

it('nombra el botón de quitar de chip', function () {
    $this->blade('<x-kore::chip removable>Etiqueta</x-kore::chip>')
        ->assertSee('aria-label="Quitar"', false);
});

it('saca las estrellas de un rating de solo lectura del recorrido de tabulación', function () {
    // Son botones que no hacen nada: dejarlos en el tabulador obliga a
    // atravesarlos uno a uno sin poder cambiar nada. Y sin `aria-label` —que
    // solo se emitía en modo interactivo— eran además controles sin nombre.
    $this->blade('<x-kore::rating label="Nota" name="n" readonly />')
        ->assertSee('tabindex="-1"', false)
        ->assertSee('aria-hidden="true"', false);
});

it('permite traducir los nombres desde la configuración', function () {
    config()->set('kore-ui.form.translations.increment', 'Más');
    config()->set('kore-ui.form.translations.decrement', 'Menos');

    $this->blade('<x-kore::number label="Cantidad" name="n" />')
        ->assertSee('aria-label="Más"', false)
        ->assertSee('aria-label="Menos"', false);
});
