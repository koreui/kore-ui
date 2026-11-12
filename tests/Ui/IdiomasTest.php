<?php

use KoreUi\Core\Support\Translations;

/**
 * Los textos de la librería en otro idioma.
 *
 * El español vive en `config/kore-ui.php`; `kore-ui.locale` aplica encima un paquete
 * de `lang/{idioma}/kore-ui.php`. Lo que se comprueba aquí es que el paquete cubre
 * TODO lo que el config tiene —una clave que falte se queda en español en mitad de
 * una pantalla en inglés— y que aplicarlo no pisa lo que la aplicación ya cambió.
 */

/** @return array<string, mixed> */
function paqueteEnIngles(): array
{
    return require __DIR__.'/../../lang/en/kore-ui.php';
}

/** Los marcadores de un texto: `:max`, `{size}`. */
function marcadoresDelTexto(string $texto): array
{
    preg_match_all('/:([a-z][a-z_]*)|\{([a-zA-Z]+)\}/', $texto, $m);
    $marcadores = array_values(array_unique(array_filter(array_merge($m[1], $m[2]))));
    sort($marcadores);

    return $marcadores;
}

it('el paquete en inglés tiene exactamente las claves del config', function (string $seccion) {
    $config = array_keys(Translations::factory());
    $delConfig = array_values(array_filter(array_map(
        fn (string $ruta) => str_starts_with($ruta, $seccion.'.translations.') ? substr($ruta, strlen($seccion.'.translations.')) : null,
        $config,
    )));
    $delPaquete = array_keys(paqueteEnIngles()[$seccion]);

    expect(array_values(array_diff($delConfig, $delPaquete)))->toBe([], "Faltan en lang/en/kore-ui.php ({$seccion})")
        ->and(array_values(array_diff($delPaquete, $delConfig)))->toBe([], "Sobran en lang/en/kore-ui.php ({$seccion}): no están en el config");
})->with(Translations::SECTIONS);

it('y los tres textos sueltos del config', function () {
    expect(array_keys(paqueteEnIngles()['loose']))->toBe(Translations::LOOSE);
});

it('cada texto lleva los mismos marcadores que el del config', function () {
    $fabrica = Translations::factory();
    $distintos = [];

    foreach (Translations::pack('en') as $ruta => $texto) {
        if (marcadoresDelTexto($texto) !== marcadoresDelTexto((string) $fabrica[$ruta])) {
            $distintos[] = "{$ruta}: «{$fabrica[$ruta]}» / «{$texto}»";
        }
    }

    expect($distintos)->toBe([], "Marcadores distintos:\n".implode("\n", $distintos));
});

it('ningún texto del paquete se quedó en español', function () {
    // Iguales a propósito: una URL, un símbolo, o la misma palabra en los dos idiomas.
    $iguales = ['form.translations.editor_url_placeholder', 'ui.translations.no'];
    $fabrica = Translations::factory();
    $sinTraducir = [];

    foreach (Translations::pack('en') as $ruta => $texto) {
        if ($texto === $fabrica[$ruta] && ! in_array($ruta, $iguales, true)) {
            $sinTraducir[] = "{$ruta}: «{$texto}»";
        }
    }

    expect($sinTraducir)->toBe([], "Iguales al español:\n".implode("\n", $sinTraducir));
});

it('de fábrica no cambia nada: los textos son los del config', function () {
    expect(config('kore-ui.locale'))->toBeNull()
        ->and(config('kore-ui.form.translations.clear'))->toBe('Limpiar')
        ->and(config('kore-ui.datatable.empty_text'))->toBe('No se encontraron resultados');
});

it('con un idioma fijo se aplica su paquete, también a los textos sueltos', function () {
    config()->set('kore-ui.locale', 'en');
    Translations::apply();

    expect(config('kore-ui.form.translations.clear'))->toBe('Clear')
        ->and(config('kore-ui.ui.translations.close'))->toBe('Close')
        ->and(config('kore-ui.datatable.translations.showing'))->toBe('Showing :from to :to of :total results')
        ->and(config('kore-ui.datatable.empty_text'))->toBe('No results found')
        ->and(config('kore-ui.spotlight.placeholder'))->toBe('Search actions, pages...');
});

it('y llega a lo que pinta un componente', function () {
    config()->set('kore-ui.locale', 'en');
    Translations::apply();

    $this->blade('<x-kore::datepicker name="d" />')
        ->assertSee('Previous month', false)
        ->assertDontSee('Mes anterior', false);
});

it('con auto sigue el idioma de la aplicación, y vuelve al español si cambia', function () {
    config()->set('kore-ui.locale', 'auto');

    // Así cambia el idioma un middleware: después del boot() del proveedor.
    app()->setLocale('en_GB');
    expect(config('kore-ui.form.translations.search'))->toBe('Search');

    app()->setLocale('es');
    expect(config('kore-ui.form.translations.search'))->toBe('Buscar');
});

it('un idioma sin paquete deja los textos como están', function () {
    config()->set('kore-ui.locale', 'auto');
    app()->setLocale('fr');

    expect(config('kore-ui.form.translations.search'))->toBe('Buscar');
});

it('un texto que la aplicación cambió en su config no se pisa', function () {
    // Lo que tendría una aplicación con el config publicado y una clave editada.
    config()->set('kore-ui.form.translations.search', 'Encontrar');

    config()->set('kore-ui.locale', 'en');
    Translations::apply();

    expect(config('kore-ui.form.translations.search'))->toBe('Encontrar')
        ->and(config('kore-ui.form.translations.clear'))->toBe('Clear');
});

it('un paquete publicado por la aplicación gana al de la librería', function () {
    app('translator')->addLines(['kore-ui.form.clear' => 'Wipe'], 'en', 'kore-ui');

    config()->set('kore-ui.locale', 'en');
    Translations::apply();

    expect(config('kore-ui.form.translations.clear'))->toBe('Wipe');
});

it('un texto cambiado en tiempo de ejecución sobrevive a que Livewire fije el idioma', function () {
    // Livewire llama a setLocale() en cada petición. La primera versión de esto restauraba
    // una foto del config en cada llamada y deshacía este cambio sin avisar.
    config()->set('kore-ui.datatable.translations.remove_filter', 'Remove :filtro');
    app()->setLocale('en');
    app()->setLocale('es');

    expect(config('kore-ui.datatable.translations.remove_filter'))->toBe('Remove :filtro');
});

it('y también con un paquete aplicado: lo que se cambia después manda', function () {
    config()->set('kore-ui.locale', 'auto');
    app()->setLocale('en');
    expect(config('kore-ui.form.translations.clear'))->toBe('Clear');

    config()->set('kore-ui.form.translations.clear', 'Reset');
    app()->setLocale('en');
    expect(config('kore-ui.form.translations.clear'))->toBe('Reset');

    // Y al volver al español no se «restaura» por encima.
    app()->setLocale('es');
    expect(config('kore-ui.form.translations.clear'))->toBe('Reset');
});

it('quitar el idioma devuelve el español', function () {
    config()->set('kore-ui.locale', 'en');
    Translations::apply();
    expect(config('kore-ui.ui.translations.close'))->toBe('Close');

    config()->set('kore-ui.locale', null);
    Translations::apply();
    expect(config('kore-ui.ui.translations.close'))->toBe('Cerrar');
});
