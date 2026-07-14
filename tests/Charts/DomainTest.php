<?php

use KoreUi\Charts\Domain;

describe('el caso normal', function () {
    it('coge el mínimo y el máximo de todas las series juntas', function () {
        $domain = Domain::fromSeries([[10, 40], [5, 90]], nice: false);

        expect($domain->toArray())->toBe([5.0, 90.0]);
    });

    it('redondea el dominio a valores bonitos', function () {
        $domain = Domain::fromSeries([[1240, 3180, 6120]], tickCount: 5);

        // Sin nice(), el eje remataría en "6120" y los ticks saldrían en 1224, 2448...
        // Con nice() el dominio se abre HACIA FUERA hasta caer en valores redondos. Ojo:
        // hacia fuera desde el dato real, no hasta el cero — el cero solo lo mete quien lo
        // necesita (una barra), y esto es una línea.
        expect($domain->toArray())->toBe([1000.0, 7000.0]);
        expect($domain->ticks(5))->toBe([1000.0, 2000.0, 3000.0, 4000.0, 5000.0, 6000.0, 7000.0]);
    });

    it('llega al cero cuando la marca lo exige, y sigue siendo redondo', function () {
        $domain = Domain::fromSeries([[1240, 3180, 6120]], includeZero: true, tickCount: 5);

        expect($domain->toArray())->toBe([0.0, 7000.0]);
    });
});

describe('el cero', function () {
    it('lo mete en el dominio si la marca lo exige', function () {
        // Una BARRA necesita el cero: su longitud ES el valor. Una barra que empieza en 40
        // miente sobre la proporción entre las barras.
        $domain = Domain::fromSeries([[40, 50, 60]], includeZero: true, nice: false);

        expect($domain->min)->toBe(0.0);
    });

    it('no lo mete si la marca no lo exige', function () {
        // Una LÍNEA no lo necesita, y forzarlo aplasta la señal contra el techo.
        $domain = Domain::fromSeries([[40, 50, 60]], includeZero: false, nice: false);

        expect($domain->min)->toBe(40.0);
    });

    it('mete el cero por arriba si todos los valores son negativos', function () {
        $domain = Domain::fromSeries([[-40, -50, -60]], includeZero: true, nice: false);

        expect($domain->toArray())->toBe([-60.0, 0.0]);
    });
});

describe('valores negativos', function () {
    it('conserva el rango completo cuando la serie cruza el cero', function () {
        $domain = Domain::fromSeries([[-30, 10, 50]], nice: false);

        expect($domain->toArray())->toBe([-30.0, 50.0]);
        expect($domain->spansZero())->toBeTrue();
    });
});

describe('dominios degenerados', function () {
    // Sin tratar, todos estos dividen por cero y siembran NaN en el atributo `d`. Y un solo
    // NaN hace que el navegador descarte el <path> ENTERO, en silencio: la serie desaparece
    // sin un error en consola y nadie sabe por qué.

    it('abre un margen cuando todos los valores son iguales', function () {
        $domain = Domain::fromSeries([[50, 50, 50]], nice: false);

        expect($domain->min)->toBeLessThan(50.0);
        expect($domain->max)->toBeGreaterThan(50.0);
    });

    it('abre un margen con un solo punto', function () {
        $domain = Domain::fromSeries([[7]], nice: false);

        expect($domain->min)->not->toBe($domain->max);
    });

    it('no inventa negativos cuando el único valor es cero', function () {
        // [-1, 1] sería absurdo: nadie tiene ventas negativas por defecto.
        $domain = Domain::fromSeries([[0, 0]], nice: false);

        expect($domain->toArray())->toBe([0.0, 1.0]);
    });

    it('marca como vacía una serie sin datos', function () {
        expect(Domain::fromSeries([])->empty)->toBeTrue();
        expect(Domain::fromSeries([[]])->empty)->toBeTrue();
    });

    it('marca como vacía una serie que es toda huecos', function () {
        expect(Domain::fromSeries([[null, null, null]])->empty)->toBeTrue();
    });
});

describe('basura en los datos', function () {
    it('ignora los huecos, que no son ceros', function () {
        $domain = Domain::fromSeries([[10, null, 30]], nice: false);

        expect($domain->toArray())->toBe([10.0, 30.0]);   // no [0, 30]
    });

    it('ignora INF y NAN en vez de contagiarlos a todo el eje', function () {
        $domain = Domain::fromSeries([[10, INF, 30, NAN, -INF]], nice: false);

        expect($domain->toArray())->toBe([10.0, 30.0]);
    });
});

describe('límites explícitos', function () {
    it('respeta el min y el max del usuario como un contrato', function () {
        // Si el usuario fija el máximo en 80, nice() no puede subirlo a 100 por su cuenta.
        $domain = Domain::fromSeries([[10, 50]], min: 0, max: 80);

        expect($domain->toArray())->toBe([0.0, 80.0]);
    });
});
