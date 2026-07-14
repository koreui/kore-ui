<?php

use KoreUi\Charts\Time\TimeInterval;

$madrid = new DateTimeZone('Europe/Madrid');
$at = fn (string $when) => new DateTimeImmutable($when, new DateTimeZone('Europe/Madrid'));

describe('truncar', function () use ($at) {
    it('lleva la fecha a la frontera de su unidad', function () use ($at) {
        $momento = $at('2026-02-14 15:47:23');

        expect(TimeInterval::minute()->floor($momento)->format('H:i:s'))->toBe('15:47:00');
        expect(TimeInterval::hour()->floor($momento)->format('H:i:s'))->toBe('15:00:00');
        expect(TimeInterval::day()->floor($momento)->format('Y-m-d H:i'))->toBe('2026-02-14 00:00');
        expect(TimeInterval::month()->floor($momento)->format('Y-m-d'))->toBe('2026-02-01');
        expect(TimeInterval::year()->floor($momento)->format('Y-m-d'))->toBe('2026-01-01');
    });

    it('la semana empieza en lunes (ISO), no en domingo', function () use ($at) {
        // 2026-02-14 es sábado.
        expect(TimeInterval::week()->floor($at('2026-02-14'))->format('Y-m-d'))->toBe('2026-02-09');
    });

    it('truncar una frontera no la mueve', function () use ($at) {
        $lunes = $at('2026-02-09 00:00:00');

        expect(TimeInterval::week()->floor($lunes))->toEqual($lunes);
        expect(TimeInterval::day()->floor($lunes))->toEqual($lunes);
    });
});

/**
 * El cambio de hora.
 *
 * `DateTimeImmutable::modify('+1 day')` hace aritmética de CALENDARIO, así que esto sale bien
 * solo. El objetivo de estos tests no es comprobar que PHP funciona: es que **siga saliendo
 * bien** el día que alguien decida que sumar un día es sumar 86.400 segundos.
 *
 * Ése, exactamente, es el bug que d3 tiene que parchear volviendo a truncar después de cada
 * salto — porque en JavaScript una fecha ES un número de milisegundos y no hay otra.
 */
describe('el cambio de hora', function () use ($at) {
    it('sobrevive al día de 23 horas', function () use ($at) {
        // En Madrid, el 29 de marzo de 2026 las 02:00 no existen: se salta a las 03:00.
        $antes = $at('2026-03-28 00:00:00');
        $despues = TimeInterval::day()->offset($antes, 1);

        expect($despues->format('Y-m-d H:i'))->toBe('2026-03-29 00:00');

        // Y el día siguiente sigue empezando a medianoche, no a la una.
        $siguiente = TimeInterval::day()->offset($despues, 1);
        expect($siguiente->format('Y-m-d H:i'))->toBe('2026-03-30 00:00');

        // Aunque entre medias sólo pasaran 23 horas de reloj de verdad.
        expect($siguiente->getTimestamp() - $despues->getTimestamp())->toBe(23 * 3600);
    });

    it('sobrevive al día de 25 horas', function () use ($at) {
        $dia = $at('2026-10-25 00:00:00');
        $siguiente = TimeInterval::day()->offset($dia, 1);

        expect($siguiente->format('Y-m-d H:i'))->toBe('2026-10-26 00:00');
        expect($siguiente->getTimestamp() - $dia->getTimestamp())->toBe(25 * 3600);
    });

    it('los ticks diarios no se descuadran al cruzar el cambio de hora', function () use ($at) {
        $ticks = TimeInterval::day()->range($at('2026-03-27'), $at('2026-04-01'));

        // Todos a medianoche, ni uno a las 23:00 ni a la 01:00.
        foreach ($ticks as $tick) {
            expect($tick->format('H:i:s'))->toBe('00:00:00');
        }

        expect(array_map(fn ($t) => $t->format('m-d'), $ticks))
            ->toBe(['03-27', '03-28', '03-29', '03-30', '03-31', '04-01']);
    });

    it('los ticks horarios saltan la hora que no existe', function () use ($at) {
        $ticks = TimeInterval::hour()->range($at('2026-03-29 00:00'), $at('2026-03-29 06:00'), 3);

        // Las 02:00 no existen. Ni se inventan, ni se repiten.
        expect(array_map(fn ($t) => $t->format('H:i'), $ticks))->toBe(['00:00', '03:00', '06:00']);
    });
});

describe('enumerar fronteras', function () use ($at) {
    it('ancla los ticks al calendario, no al principio del rango', function () use ($at) {
        // «Cada 3 meses» son enero, abril, julio y octubre — SIEMPRE. No «tres meses después de
        // donde empiece la ventana». Sin esto, arrastrar el gráfico un mes haría saltar todas las
        // etiquetas del eje.
        $desde = TimeInterval::month()->range($at('2026-02-01'), $at('2026-12-31'), 3);

        expect(array_map(fn ($t) => $t->format('M'), $desde))->toBe(['Apr', 'Jul', 'Oct']);

        // Y movida la ventana un mes, los ticks siguen donde estaban.
        $movido = TimeInterval::month()->range($at('2026-03-01'), $at('2026-12-31'), 3);

        expect(array_map(fn ($t) => $t->format('M'), $movido))->toBe(['Apr', 'Jul', 'Oct']);
    });

    it('cuenta los días desde la época, no el día del mes', function () use ($at) {
        // Contar el día DEL MES reinicia la cuenta cada mes: con «cada 2 días», el 31 y el 1
        // quedarían pegados. Es el bug que d3 arregló en su 3.x.
        $ticks = TimeInterval::day()->range($at('2026-01-28'), $at('2026-02-04'), 2);
        $dias = array_map(fn ($t) => (int) $t->format('j'), $ticks);

        // La secuencia no se reinicia al cambiar de mes: entre dos ticks consecutivos siempre
        // hay exactamente dos días.
        for ($i = 1; $i < count($ticks); $i++) {
            $delta = $ticks[$i]->diff($ticks[$i - 1])->days;
            expect($delta)->toBe(2);
        }

        expect($dias)->not->toBeEmpty();
    });

    it('no devuelve nada si el rango está del revés', function () use ($at) {
        expect(TimeInterval::day()->range($at('2026-02-14'), $at('2026-02-01')))->toBe([]);
    });
});
