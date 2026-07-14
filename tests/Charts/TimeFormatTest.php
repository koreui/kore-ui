<?php

use KoreUi\Charts\Time\TimeFormat;

$at = fn (string $when) => new DateTimeImmutable($when, new DateTimeZone('Europe/Madrid'));

/**
 * La escalera: cada fecha se escribe con la granularidad inmediatamente inferior a la frontera
 * más gruesa que respeta. Si no cae en un día 1, lo que la distingue de sus vecinas es el día;
 * si sí cae, lo que la distingue es el mes.
 */
describe('la escalera', function () use ($at) {
    $format = new TimeFormat('es');

    it('un día cualquiera imprime el día', function () use ($at, $format) {
        expect($format->tick($at('2026-02-14'))['label'])->toBe('14');
    });

    it('un día 1 imprime el MES, que es lo que lo distingue', function () use ($at, $format) {
        expect($format->tick($at('2026-02-01'))['label'])->toBe('feb.');
    });

    it('un 1 de enero imprime el AÑO', function () use ($at, $format) {
        expect($format->tick($at('2026-01-01'))['label'])->toBe('2026');
    });

    it('una hora en punto imprime la hora', function () use ($at, $format) {
        expect($format->tick($at('2026-02-14 15:00'))['label'])->toBe('15:00');
    });

    it('un minuto suelto imprime hora y minuto', function () use ($at, $format) {
        expect($format->tick($at('2026-02-14 15:30'))['label'])->toBe('15:30');
    });

    it('un segundo suelto baja a los segundos', function () use ($at, $format) {
        expect($format->tick($at('2026-02-14 15:30:07'))['label'])->toBe('15:30:07');
    });
});

/**
 * El agujero de d3.
 *
 * En un eje del 10 al 20 de enero, NINGÚN tick cae en un día 1 — así que con la escalera sola,
 * el mes no aparece por ninguna parte y el eje dice «10 11 12 13 …» y nada más. Eso no es un eje:
 * es una lista de números. La respuesta es la de uPlot: una segunda línea.
 */
describe('la línea de contexto', function () use ($at) {
    $format = new TimeFormat('es');

    it('el primer tick SIEMPRE la lleva: es el que sitúa todo el eje', function () use ($at, $format) {
        expect($format->tick($at('2026-01-14'), null)['context'])->toBe('ene. 2026');
    });

    it('no se repite mientras no cambie: sería ruido, no información', function () use ($at, $format) {
        $anterior = $at('2026-01-14');

        expect($format->tick($at('2026-01-15'), $anterior)['context'])->toBeNull();
    });

    it('vuelve a salir cuando cambia el mes', function () use ($at, $format) {
        expect($format->tick($at('2026-02-03'), $at('2026-01-28'))['context'])->toBe('feb. 2026');
    });

    it('en un eje de horas, el contexto es el DÍA', function () use ($at, $format) {
        expect($format->tick($at('2026-02-14 06:00'), null)['context'])->toBe('14 feb.');
    });

    it('en un eje de meses, el contexto es el AÑO', function () use ($at, $format) {
        expect($format->tick($at('2026-03-01'), null)['context'])->toBe('2026');
    });

    it('un eje de años no lleva contexto: el año ya está en la etiqueta', function () use ($at, $format) {
        expect($format->tick($at('2026-01-01'), null)['context'])->toBeNull();
    });
});

describe('la etiqueta de una fila (tooltip y tabla accesible)', function () use ($at) {
    $format = new TimeFormat('es');

    it('va entera: un tooltip habla de un punto fuera de todo contexto', function () use ($at, $format) {
        // «14» no le dice nada a nadie.
        expect($format->row($at('2026-02-14')))->toBe('14 feb. 2026');
    });

    it('lleva la hora si el dato la tiene', function () use ($at, $format) {
        expect($format->row($at('2026-02-14 15:30')))->toBe('14 feb. 2026, 15:30');
    });
});

it('traduce los meses sin ext-intl y sin un byte de Intl en el bundle', function () use ($at) {
    // Carbon los traduce con sus propias tablas. `Format` (el de los números) evita ext-intl a
    // propósito, porque no se puede exigir una extensión para pintar un gráfico; aquí igual.
    expect((new TimeFormat('es'))->tick($at('2026-02-01'))['label'])->toBe('feb.');
    expect((new TimeFormat('en'))->tick($at('2026-02-01'))['label'])->toBe('Feb');
    expect((new TimeFormat('fr'))->tick($at('2026-02-01'))['label'])->toBe('févr.');
});
