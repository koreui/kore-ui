<?php

use Illuminate\Support\Facades\Validator;
use KoreUi\Editor\Casts\SanitizedHtml;
use KoreUi\Editor\Rules\MaxTextLength;
use KoreUi\Editor\Rules\SafeHtml;

/**
 * Publicar lo que el editor escribe, y no fiarse de lo que llega.
 *
 * Eran las dos cosas que había que saberse de memoria: pintar con `{!! !!}` sin
 * sanear es un XSS almacenado, y los estilos del texto enriquecido vivían bajo
 * una clase interna del propio editor.
 */

it('publica markdown ya convertido', function () {
    $this->blade('<x-kore::prose :markdown="$md" />', ['md' => "## Titulo\n\nCon **negrita**"])
        ->assertSee('<h2>Titulo</h2>', false)
        ->assertSee('<strong>negrita</strong>', false);
});

it('sanea el HTML antes de pintarlo, porque después ya no hay nadie', function () {
    $this->blade('<x-kore::prose :html="$h" />', ['h' => '<p onclick="alert(1)">Hola</p><script>alert(2)</script>'])
        ->assertSee('<p>Hola</p>', false)
        ->assertDontSee('onclick', false)
        ->assertDontSee('alert(2)', false);
});

it('lleva la misma clase que el interior del editor', function () {
    // Una sola clase para los dos: es el mismo contenido en dos momentos, y
    // duplicar dieciséis reglas de CSS sería pedir que se desincronicen.
    $this->blade('<x-kore::prose :markdown="$md" />', ['md' => 'x'])->assertSee('kore-prose', false);
});

it('por slot no sanea, porque ahí puede haber cualquier cosa', function () {
    // Un componente dentro del slot no es del editor, y sanearlo se llevaría por
    // delante lo que no es suyo.
    $this->blade('<x-kore::prose><p>libre</p></x-kore::prose>')->assertSee('libre');
});

it('la regla rechaza el marcado que el editor nunca produce', function () {
    $rechaza = fn (string $valor) => Validator::make(['c' => $valor], ['c' => [new SafeHtml]])->fails();

    expect($rechaza('<p>Hola</p>'))->toBeFalse()
        ->and($rechaza('<p onclick="alert(1)">Hola</p>'))->toBeTrue()
        ->and($rechaza('<script>alert(1)</script>'))->toBeTrue()
        ->and($rechaza('<p><a href="javascript:alert(1)">x</a></p>'))->toBeTrue();
});

it('la regla acepta el vacío: eso es cosa de required', function () {
    expect(Validator::make(['c' => ''], ['c' => [new SafeHtml]])->fails())->toBeFalse()
        ->and(Validator::make(['c' => null], ['c' => [new SafeHtml]])->fails())->toBeFalse();
});

it('el límite cuenta el texto y no el marcado', function () {
    // `<p><strong>Hola</strong></p>` son 4 caracteres para quien escribe y 30
    // para `max:`. Con la regla de Laravel, el contador del editor decía «4/10»
    // y el formulario contestaba que se había pasado.
    expect(MaxTextLength::contar('<p><strong>Hola</strong></p>'))->toBe(4);

    $pasa = fn (string $v, int $max) => ! Validator::make(['c' => $v], ['c' => [new MaxTextLength($max)]])->fails();

    expect($pasa('<p><strong>Hola</strong></p>', 10))->toBeTrue()
        ->and($pasa('<p>doce chars</p>', 5))->toBeFalse();
});

it('cuenta igual que el contador del navegador', function () {
    // Si las dos cuentas no coinciden, el usuario ve un número y el servidor otro.
    expect(MaxTextLength::contar('<p>uno</p><p>dos</p>'))->toBe(7)   // «uno dos»
        ->and(MaxTextLength::contar('<ul><li>a</li><li>b</li></ul>'))->toBe(3)
        // La entidad se decodifica antes de contar: «con & entidad» son 13.
        ->and(MaxTextLength::contar('<p>con &amp; entidad</p>'))->toBe(13)
        ->and(MaxTextLength::contar(''))->toBe(0);
});

it('el cast limpia al guardar sin que nadie se acuerde', function () {
    $cast = new SanitizedHtml;
    $modelo = new class extends Illuminate\Database\Eloquent\Model {};

    expect($cast->set($modelo, 'cuerpo', '<p onclick="x()">Hola</p><script>alert(1)</script>', []))
        ->toBe('<p>Hola</p>')
        ->and($cast->set($modelo, 'cuerpo', null, []))->toBeNull();
});

it('el cast no toca lo que se lee', function () {
    // Sanear en cada lectura sería pagarlo en cada fila de cada consulta; lo que
    // entra queda limpio de una vez.
    $cast = new SanitizedHtml;
    $modelo = new class extends Illuminate\Database\Eloquent\Model {};

    expect($cast->get($modelo, 'cuerpo', '<p>lo que hubiera</p>', []))->toBe('<p>lo que hubiera</p>');
});
