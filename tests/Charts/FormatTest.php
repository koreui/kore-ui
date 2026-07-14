<?php

use KoreUi\Charts\Format;

describe('números', function () {
    it('usa los separadores del locale', function () {
        expect((new Format)->apply(1234567))->toBe('1.234.567');
    });

    it('respeta los decimales que exige el paso del eje', function () {
        // Sin esto, un paso de 0,25 imprimiría "0,3" y el eje dejaría de sumar.
        expect((new Format)->apply(0.25, decimals: 2))->toBe('0,25');
        expect((new Format)->apply(1.5, decimals: 1))->toBe('1,5');
    });

    it('no imprime "-0"', function () {
        // -0 existe en coma flotante. En un eje es sencillamente ridículo.
        expect((new Format)->apply(-0.0))->toBe('0');
    });
});

describe('moneda y porcentaje', function () {
    it('pone el símbolo detrás en español', function () {
        $format = new Format(style: 'currency', decimals: 2);

        expect($format->apply(1234.5))->toBe('1.234,50 €');
    });

    it('lo pone delante si se le pide', function () {
        $format = new Format(style: 'currency', decimals: 2, currency: '$', currencyAfter: false, decimalSeparator: '.', thousandsSeparator: ',');

        expect($format->apply(1234.5))->toBe('$1,234.50');
    });

    it('escribe porcentajes', function () {
        expect((new Format(style: 'percent'))->apply(42))->toBe('42 %');
    });
});

describe('compacto', function () {
    // Un eje con "1.200.000" repetido cinco veces no se lee: se descifra.
    it('abrevia las magnitudes grandes', function () {
        $format = new Format(style: 'compact');

        expect($format->apply(1200))->toBe('1,2k');
        expect($format->apply(2500000))->toBe('2,5M');
        expect($format->apply(3000000000))->toBe('3MM');
    });

    it('no añade un decimal que no aporta', function () {
        $format = new Format(style: 'compact');

        expect($format->apply(12000))->toBe('12k');    // no "12,0k"
        expect($format->apply(500))->toBe('500');      // por debajo de mil, no se toca
    });

    it('funciona con negativos', function () {
        expect((new Format(style: 'compact'))->apply(-1500))->toBe('-1,5k');
    });
});

describe('huecos y basura', function () {
    it('escribe un hueco como hueco, no como cero', function () {
        // "0" sería mentir: no es que valga cero, es que no hay dato.
        expect((new Format)->apply(null))->toBe('—');
        expect((new Format)->apply(NAN))->toBe('—');
        expect((new Format)->apply(INF))->toBe('—');
    });
});

describe('prefijos y sufijos', function () {
    it('permite unidades arbitrarias', function () {
        $format = new Format(suffix: ' ms');

        expect($format->apply(250))->toBe('250 ms');
    });
});

describe('los decimales de una serie', function () {
    it('deduce los que hacen falta para no mentir', function () {
        // El eje saca sus decimales del paso entre ticks. Una serie no tiene paso: sin esto,
        // un sensor que marca 21,4 °C se escribía "21" en el tooltip y en la tabla accesible.
        expect(Format::decimalsFor([21.4, 23.1, null, 25.8]))->toBe(1);
        expect(Format::decimalsFor([1240, 3180, 2470]))->toBe(0);
        expect(Format::decimalsFor([0.125, 1.5]))->toBe(2);   // topado en 2
    });

    it('los mismos para toda la serie, no valor a valor', function () {
        // Una columna con "21,4" encima de "23" se lee peor que con "21,4" encima de "23,0".
        // Es la misma razón por la que un eje usa los mismos decimales en todos sus ticks.
        expect(Format::decimalsFor([21.4, 23]))->toBe(1);

        $format = new Format;

        expect($format->apply(23, Format::decimalsFor([21.4, 23])))->toBe('23,0');
    });

    it('ignora los huecos y la basura', function () {
        expect(Format::decimalsFor([null, NAN, INF]))->toBe(0);
    });

    it('no se deja engañar por la coma flotante', function () {
        // 0.1 + 0.2 no es 0.3, y round($v, 1) == $v sería falso para valores grandes.
        expect(Format::decimalsFor([0.1 + 0.2]))->toBe(1);
        expect(Format::decimalsFor([1_000_000.5]))->toBe(1);
    });
});
