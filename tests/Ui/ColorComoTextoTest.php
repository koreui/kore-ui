<?php

use Symfony\Component\Finder\Finder;

/**
 * Un color de la paleta NO se puede usar como texto sobre su propio tinte.
 *
 * `--kore-success` y compañía están pensados para ser FONDO: tienen la
 * luminosidad de un fondo saturado, no la de un texto legible. Pintados como
 * color de letra sobre un fondo `bg` del mismo color al diez por ciento —que es lo que hacen todas
 * las variantes `soft`— se quedan muy por debajo de AA.
 *
 * Medido en un navegador, antes del arreglo, con el fondo real compuesto capa a
 * capa: **success 3,01 · info 3,24 · destructive 3,91 · primary 4,08**, y
 * `warning` en 2,07. De las 21 combinaciones de un badge, doce por debajo de
 * 4,5; de las 39 de un botón, veinticuatro.
 *
 * La solución ya existía en la librería y solo se había aplicado a un color de
 * cinco: el token `--kore-warning-text`, con su nota en el CSS explicando
 * exactamente esto. Ahora están los cinco, calibrados midiendo.
 *
 * Este cepo es para el próximo componente con variantes de color. Lo que mide de
 * verdad —el contraste, en un navegador y en los dos temas— está en
 * `demo/e2e/specs/50-presentacion-color.spec.js`.
 */
it('ningún color de la paleta se usa como texto sobre su propio tinte', function () {
    $problemas = [];

    // Los colores cuyo token base es un FONDO. `muted` y `secondary` no entran:
    // sus parejas son `-fg`, que ya es un color de texto.
    $colores = ['primary', 'success', 'warning', 'destructive', 'info'];

    $vistas = Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php');

    foreach ($vistas as $vista) {
        $contenido = $vista->getContents();

        foreach ($colores as $color) {
            // `text-kore-success` a secas, pero no `text-kore-success-fg` ni
            // `text-kore-success-text`, que sí son colores de texto.
            $patron = '/text-kore-'.$color.'(?![\w-])/';

            foreach (preg_split('/\R/', $contenido) as $n => $linea) {
                if (! preg_match($patron, $linea)) {
                    continue;
                }

                // Solo molesta cuando el fondo es el tinte del MISMO color: sobre
                // el fondo de la página el color base sí se lee.
                if (! preg_match('/bg-kore-'.$color.'\/\d+/', $linea)) {
                    continue;
                }

                $problemas[] = sprintf(
                    '%s:%d  text-kore-%s sobre su propio tinte',
                    str_replace(realpath(__DIR__.'/../../').'/', '', $vista->getRealPath()),
                    $n + 1,
                    $color
                );
            }
        }
    }

    expect($problemas)->toBe([], implode("\n", array_merge(
        ['El color base es un FONDO; como texto sobre su propio tinte no llega a AA:'],
        $problemas,
        ['', 'Usa text-kore-{color}-text, que está calibrado para eso.'],
    )));
});

/** Y los cinco tokens tienen que existir en los dos temas. */
it('define el token de texto de los cinco colores en claro y en oscuro', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/kore-theme.css');

    // El bloque del tema oscuro empieza donde se redefine la paleta.
    $corte = strpos($css, '--kore-primary: oklch(0.70 0.20 250)');
    expect($corte)->toBeGreaterThan(0, 'no se encuentra el bloque del tema oscuro');

    $claro = substr($css, 0, $corte);
    $oscuro = substr($css, $corte);

    foreach (['primary', 'success', 'warning', 'destructive', 'info'] as $color) {
        // `toContain` con dos argumentos comprueba que estén LAS DOS cadenas, así
        // que el mensaje no puede ir ahí dentro: buscaba el texto en español
        // dentro del CSS y fallaba siempre.
        expect(str_contains($claro, "--kore-{$color}-text:"))
            ->toBeTrue("falta --kore-{$color}-text en el tema claro");
        expect(str_contains($oscuro, "--kore-{$color}-text:"))
            ->toBeTrue("falta --kore-{$color}-text en el tema oscuro");

        // Y registrado en `@theme inline`, o Tailwind no genera la clase.
        expect(str_contains($css, "--color-kore-{$color}-text: var(--kore-{$color}-text);"))
            ->toBeTrue("--color-kore-{$color}-text no está en @theme inline");
    }
});
