<?php

use KoreUi\Charts\Scales\BandScale;
use KoreUi\Charts\Scales\LinearScale;

describe('escala lineal', function () {
    it('lleva el dominio al espacio 0-100', function () {
        $scale = LinearScale::make([0, 200]);

        expect($scale->at(0))->toBe(0.0);
        expect($scale->at(100))->toBe(50.0);
        expect($scale->at(200))->toBe(100.0);
    });

    it('invierte la Y: en pantalla el 0 está arriba, pero el valor más alto va arriba', function () {
        $scale = LinearScale::vertical([0, 100]);

        expect($scale->at(0))->toBe(100.0);    // el valor mínimo, abajo del todo
        expect($scale->at(100))->toBe(0.0);    // el valor máximo, arriba del todo
    });

    it('sabe dónde cae el cero, que es de donde crece una barra', function () {
        expect(LinearScale::vertical([-50, 50])->zero())->toBe(50.0);
        expect(LinearScale::vertical([0, 100])->zero())->toBe(100.0);
    });

    it('ancla el cero al borde cuando el dominio no lo contiene', function () {
        // Con un dominio [20, 100], el cero cae fuera. Una barra tiene que crecer desde el
        // suelo del gráfico, no desde una posición imaginaria fuera de la caja.
        expect(LinearScale::vertical([20, 100])->zero())->toBe(100.0);
    });

    it('no divide por cero con un dominio plano', function () {
        // Un solo NaN en el `d` hace que el navegador descarte el <path> entero, en silencio.
        $scale = LinearScale::make([50, 50]);

        expect(is_finite($scale->at(50)))->toBeTrue();
        expect(is_nan($scale->at(50)))->toBeFalse();
    });

    it('sabe volver del espacio del gráfico al dato', function () {
        $scale = LinearScale::make([0, 200]);

        expect($scale->invert(50.0))->toBe(100.0);
    });
});

describe('escala de bandas', function () {
    it('reparte las categorías en bandas iguales', function () {
        $scale = new BandScale(['Ene', 'Feb', 'Mar', 'Abr'], padding: 0.0);

        expect($scale->step())->toBe(25.0);
        expect($scale->bandwidth())->toBe(25.0);
        expect($scale->at('Ene'))->toBe(0.0);
        expect($scale->at('Mar'))->toBe(50.0);
    });

    it('deja hueco entre bandas sin mover el paso', function () {
        $scale = new BandScale(['A', 'B'], padding: 0.2);

        expect($scale->step())->toBe(50.0);
        expect($scale->bandwidth())->toBe(40.0);
        expect($scale->at('A'))->toBe(5.0);      // centrada dentro de su paso
    });

    it('ancla las líneas al CENTRO de la banda', function () {
        // Si la línea se anclara al borde, no coincidiría con las barras del mismo gráfico.
        $scale = new BandScale(['A', 'B'], padding: 0.2);

        expect($scale->center('A'))->toBe(25.0);
        expect($scale->center('B'))->toBe(75.0);
    });

    it('no divide por cero sin categorías', function () {
        $scale = new BandScale([]);

        expect(is_finite($scale->step()))->toBeTrue();
        expect($scale->count())->toBe(0);
    });

    describe('modo punto (sin barras)', function () {
        it('reparte los puntos de borde a borde', function () {
            // Sin barras, anclar al centro de una banda imaginaria deja media banda vacía a
            // cada lado: con 6 categorías se pierde el 16 % del ancho y el área parece cortada.
            $scale = new BandScale(['A', 'B', 'C', 'D', 'E'], point: true);

            expect($scale->centerAt(0))->toBe(0.0);
            expect($scale->centerAt(2))->toBe(50.0);
            expect($scale->centerAt(4))->toBe(100.0);
        });

        it('centra el punto único en vez de dividir por cero', function () {
            expect((new BandScale(['A'], point: true))->centerAt(0))->toBe(50.0);
        });
    });
});
