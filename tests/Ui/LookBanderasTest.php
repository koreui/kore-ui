<?php

use KoreUi\Core\Support\Look;

/**
 * Las banderas de aspecto y su cascada.
 *
 * Antes, cada superficie resolvía su aspecto a su manera: `card` leía su clave,
 * `navbar` la suya con otro `??`, `stats` pintaba borde y sombra fijos sin
 * preguntar, y el `bordered` de `table` era un prop declarado que no leía nadie.
 * Poner la librería entera en plano exigía repetir `:shadow="false"` etiqueta
 * por etiqueta.
 */

it('el prop de la etiqueta manda sobre cualquier configuración', function () {
    config()->set('kore-ui.ui.look.shadow', true);
    config()->set('kore-ui.ui.card.shadow', true);

    expect(Look::resolver('card', 'shadow', false, true))->toBeFalse();
});

it('la sección del componente gana a la global', function () {
    config()->set('kore-ui.ui.look.shadow', false);
    config()->set('kore-ui.ui.card.shadow', true);

    expect(Look::resolver('card', 'shadow', null, true))->toBeTrue();
});

it('la global vale para lo que no tiene sección propia', function () {
    config()->set('kore-ui.ui.look.shadow', false);
    config()->set('kore-ui.ui.card.shadow', null);

    expect(Look::resolver('card', 'shadow', null, true))->toBeFalse();
});

it('sin nadie que opine, manda el defecto del componente', function () {
    config()->set('kore-ui.ui.look.shadow', null);
    config()->set('kore-ui.ui.card.shadow', null);

    expect(Look::resolver('card', 'shadow', null, true))->toBeTrue()
        ->and(Look::resolver('stats', 'shadow', null, false))->toBeFalse();
});

it('acepta rutas fuera de ui para quien no vive ahí', function () {
    config()->set('kore-ui.shell.navbar.bordered', false);

    expect(Look::resolver('shell.navbar', 'bordered', null, true))->toBeFalse();
});

it('una bandera inventada es una errata, no un silencio', function () {
    // Sin esto, `config()` devolvería null para siempre y la bandera se quedaría
    // en su defecto sin que nadie se entere.
    expect(fn () => Look::resolver('card', 'sombra', null, true))
        ->toThrow(InvalidArgumentException::class);
});

it('apaga las sombras de toda la librería de una vez', function () {
    config()->set('kore-ui.ui.look.shadow', false);

    $this->blade('<x-kore::card title="Con marco">x</x-kore::card>')
        ->assertDontSee('shadow-sm', false);
});

it('y la sección del componente puede devolvérselas a uno', function () {
    config()->set('kore-ui.ui.look.shadow', false);
    config()->set('kore-ui.ui.stats.shadow', true);

    $this->blade('<x-kore::stats label="Ingresos" value="1" />')
        ->assertSee('shadow-sm', false);
});

it('el borde de la tabla ya no es un interruptor que no enciende nada', function () {
    // Estaba declarado como prop desde el principio y no lo leía nadie.
    $this->blade('<x-kore::table :headers="[\'A\']" :rows="[[\'A\' => 1]]" bordered />')
        ->assertSee('[&_td]:border-r', false);

    $this->blade('<x-kore::table :headers="[\'A\']" :rows="[[\'A\' => 1]]" />')
        ->assertDontSee('[&_td]:border-r', false);
});

it('el relleno de la tarjeta también se puede quitar desde la configuración', function () {
    config()->set('kore-ui.ui.look.padding', false);

    $this->blade('<x-kore::card>x</x-kore::card>')->assertDontSee('px-6 py-4', false);
});

it('la silueta hereda el marco del componente, no el suyo propio', function () {
    // Si la silueta pintara sombra donde el componente no la pinta, la página
    // saltaría al llegar los datos, que es justo lo que evita el skeleton.
    config()->set('kore-ui.ui.look.shadow', true);

    $conSilueta = $this->blade('<x-kore::stats label="x" value="1" skeleton />')->__toString();
    $sinSilueta = $this->blade('<x-kore::stats label="x" value="1" />')->__toString();

    expect(str_contains($conSilueta, 'shadow-sm'))->toBe(str_contains($sinSilueta, 'shadow-sm'));
});
