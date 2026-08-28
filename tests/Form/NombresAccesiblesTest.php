<?php

use Illuminate\Support\Js;

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

it('nombra el botón del ojo de password, en los dos estados', function () {
    // Decía «Show password» / «Hide password», escrito dentro de la expresión de
    // Alpine: el único texto que ni publicando las vistas se podía traducir.
    //
    // Se compara contra `Js::from` porque el nombre viaja dentro de la expresión
    // y sale de ahí escapado, con la eñe en `\u00f1`.
    $html = (string) $this->blade('<x-kore::password label="Contraseña" name="c" />');

    expect($html)->toContain((string) Js::from('Mostrar la contraseña'))
        ->and($html)->toContain((string) Js::from('Ocultar la contraseña'))
        ->and($html)->not->toContain('Show password');
});

it('nombra el botón del ojo también con el medidor puesto', function () {
    // Son dos ramas distintas de la vista, y solo una se arregló la primera vez
    // que se tocó este componente.
    $html = (string) $this->blade('<x-kore::password label="Contraseña" name="c" :strength="true" />');

    expect($html)->toContain((string) Js::from('Mostrar la contraseña'))
        ->and($html)->not->toContain('Show password');
});

/**
 * La 2.0.0 nombró los botones de limpiar de `input`, `select` y `datepicker`, y
 * los tres que faltaban se quedaron fuera del inventario: son el mismo aspa, en
 * otras tres vistas. Y el aspa de `tag-input` no es la de una etiqueta suelta
 * —esa sí tenía nombre— sino la que las borra TODAS.
 */
it('nombra el botón de limpiar de maskable, time-picker y tag-input', function () {
    $this->blade('<x-kore::maskable label="Teléfono" name="t" mask="(##) ####-####" clearable />')
        ->assertSee('aria-label="Limpiar"', false);

    $this->blade('<x-kore::time-picker label="Hora" name="h" clearable />')
        ->assertSee('aria-label="Limpiar"', false);

    $this->blade('<x-kore::tag-input label="Etiquetas" name="e" clearable />')
        ->assertSee('aria-label="Limpiar"', false);
});

it('nombra el aspa que descarta un aviso de upload', function () {
    $this->blade('<x-kore::upload label="Archivos" name="a" />')
        ->assertSee('aria-label="Descartar el aviso"', false);
});

it('nombra las cuatro flechas del reloj del calendario', function () {
    // Son otras que las del time-picker: mismo nombre, otra vista.
    $this->blade('<x-kore::datepicker label="Cuándo" name="c" with-time />')
        ->assertSee('aria-label="Subir la hora"', false)
        ->assertSee('aria-label="Bajar la hora"', false)
        ->assertSee('aria-label="Subir los minutos"', false)
        ->assertSee('aria-label="Bajar los minutos"', false);
});

/**
 * El nombre de cada estrella era «{{ $i }} de {{ $stars }} estrellas», con el
 * «de» escrito en la vista. Un conector suelto entre dos interpolaciones no se
 * traduce, y en inglés ni siquiera va en el mismo sitio.
 */
it('nombra cada estrella con una plantilla entera', function () {
    $this->blade('<x-kore::rating label="Nota" name="n" :stars="3" />')
        ->assertSee('aria-label="1 de 3 estrellas"', false)
        ->assertSee('aria-label="3 de 3 estrellas"', false);
});

it('permite traducir el nombre de las estrellas, con su orden', function () {
    config()->set('kore-ui.form.translations.rating_stars', ':n of :total stars');

    $this->blade('<x-kore::rating label="Score" name="n" :stars="5" />')
        ->assertSee('aria-label="2 of 5 stars"', false);
});

/**
 * El botón AM/PM se anunciaba «AM» y ya: eso es el estado, no lo que hace al
 * pulsarlo. La clave `toggle_period` estaba en la configuración desde la 2.0.0 y
 * no la leía nadie — se creó y no se llegó a conectar.
 */
it('dice qué hace el botón AM/PM, sin perder el estado', function () {
    $reloj = (string) $this->blade('<x-kore::time-picker label="Hora" name="h" time-format="12" />');

    expect($reloj)->toContain('Cambiar entre AM y PM')
        ->and($reloj)->toContain('x-text="ampm"');

    $calendario = (string) $this->blade('<x-kore::datepicker label="Cuándo" name="c" with-time time-format="12" />');

    expect($calendario)->toContain('Cambiar entre AM y PM');
});

it('permite traducir los nombres desde la configuración', function () {
    config()->set('kore-ui.form.translations.increment', 'Más');
    config()->set('kore-ui.form.translations.decrement', 'Menos');

    $this->blade('<x-kore::number label="Cantidad" name="n" />')
        ->assertSee('aria-label="Más"', false)
        ->assertSee('aria-label="Menos"', false);
});
