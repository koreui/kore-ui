<?php

/**
 * Las opciones del select tienen que poder cambiar desde el servidor.
 *
 * Es el patrón del select dependiente —provincia según país— y no funcionaba.
 * Las opciones viajaban dentro del `x-data`, que Alpine evalúa una sola vez: al
 * cambiar `:options` el atributo se actualizaba en el DOM pero nadie lo volvía a
 * leer, y el panel —que además está teleportado a `body`, fuera del alcance del
 * morph— seguía enseñando la lista de la primera carga. El estado del componente
 * y lo que el usuario veía se separaban sin ningún aviso.
 *
 * Ahora viajan en un nodo JSON aparte que Livewire sí actualiza, y el plugin lo
 * vigila con un MutationObserver.
 */
it('emite las opciones en un nodo propio, no dentro del x-data', function () {
    $html = (string) $this->blade('<x-kore::select label="País" name="p" :options="[\'es\' => \'España\']" />');

    expect($html)->toContain('data-kore-select-options')
        ->and($html)->toContain('type="application/json"')
        ->and($html)->toContain('optionsId:');
});

it('el nodo de opciones queda fuera de la raíz del componente', function () {
    // Dentro no serviría: la raíz es la que Alpine gobierna, y en `multiple`
    // lleva además `wire:ignore`, que congelaría el nodo.
    $html = (string) $this->blade('<x-kore::select label="País" name="p" multiple :options="[\'es\' => \'España\']" />');

    $posNodo = strpos($html, 'data-kore-select-options');
    $posRaiz = strpos($html, 'x-data="KoreSelect');

    expect($posNodo)->toBeLessThan($posRaiz);
});

it('el JSON de opciones lleva los valores tal cual', function () {
    $html = (string) $this->blade('<x-kore::select label="País" name="p" :options="[\'es\' => \'España\', \'fr\' => \'Francia\']" />');

    preg_match('/data-kore-select-options>(.*?)<\/script>/s', $html, $m);
    $opciones = json_decode($m[1] ?? '[]', true);

    expect($opciones)->toHaveCount(2)
        ->and($opciones[0]['label'])->toBe('España')
        ->and($opciones[0]['value'])->toBe('es');
});

it('el select nativo sigue emitiendo sus <option> como siempre', function () {
    // Es el control del arreglo: el modo nativo nunca tuvo el problema, y si
    // dejara de reflejar las opciones querría decir que se rompió al tocar el
    // otro camino.
    $this->blade('<x-kore::select label="País" name="p" native :options="[\'es\' => \'España\']" />')
        ->assertSee('<option value="es">España</option>', false);
});

it('no pone wire:ignore en un select sencillo', function () {
    // Congelaría la raíz. Lo que cerraba el desplegable en cada re-render ajeno
    // no era el morph sino el id no determinista del campo: ver IdContext.
    $sencillo = (string) $this->blade('<x-kore::select label="P" name="p" :options="[]" />');
    $multiple = (string) $this->blade('<x-kore::select label="P" name="p" multiple :options="[]" />');

    expect($sencillo)->not->toContain('wire:ignore')
        ->and($multiple)->toContain('wire:ignore');
});

it('usa los textos de la configuración y no cadenas en inglés', function () {
    $html = (string) $this->blade('<x-kore::select label="P" name="p" searchable :options="[]" />');

    expect($html)->toContain('Sin resultados')
        ->and($html)->not->toContain('No options found')
        ->and($html)->not->toContain('Search...');
});
