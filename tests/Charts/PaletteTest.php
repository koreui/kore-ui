<?php

use KoreUi\Charts\Palette;

describe('el color viaja como token, no como valor', function () {
    it('devuelve una referencia CSS, no un color', function () {
        // Ésta es la razón de que el gráfico se repinte solo al cambiar de tema, sin JS.
        expect(Palette::token(1))->toBe('var(--kore-chart-1)');
        expect(Palette::token(8))->toBe('var(--kore-chart-8)');
    });

    it('acepta un token semántico si el usuario lo pide a propósito', function () {
        // Una serie que literalmente significa "errores" sí debería ir en rojo.
        expect(Palette::resolve(1, 'destructive'))->toBe('var(--kore-destructive)');
    });

    it('deja pasar un color literal del usuario', function () {
        expect(Palette::resolve(1, 'oklch(0.5 0.2 30)'))->toBe('oklch(0.5 0.2 30)');
    });

    it('usa el slot cuando no hay color explícito', function () {
        expect(Palette::resolve(3))->toBe('var(--kore-chart-3)');
        expect(Palette::resolve(3, ''))->toBe('var(--kore-chart-3)');
    });
});

describe('la paleta no se cicla', function () {
    it('asigna el slot por orden de registro de la marca', function () {
        // El color sigue a la ENTIDAD, no a su posición entre las visibles. Si se asignara
        // por índice de serie visible, ocultar la serie 2 repintaría la 3 con el color de
        // la 2 y el lector creería estar mirando otra cosa.
        expect(Palette::slotFor(1))->toBe(1);
        expect(Palette::slotFor(5))->toBe(5);
    });

    it('se niega a pintar una novena serie en vez de repetir un color', function () {
        // Repetir el color de la serie 1 en la novena es peor que no pintarla: el lector
        // deja de poder distinguirlas y el gráfico miente.
        expect(fn () => Palette::slotFor(9))->toThrow(InvalidArgumentException::class, 'Otros');
        expect(fn () => Palette::token(9))->toThrow(InvalidArgumentException::class);
        expect(fn () => Palette::token(0))->toThrow(InvalidArgumentException::class);
    });
});

describe('scatter: solo aguanta 5 series', function () {
    it('usa el subconjunto que sobrevive al daltonismo', function () {
        // En barras y líneas solo se tocan los vecinos. En un scatter, CUALQUIER par puede
        // quedar uno al lado del otro, así que hay que distinguir los ocho entre sí — y ahí
        // magenta y teal colapsan al mismo color bajo deuteranopia (ΔE 2.4, medido).
        expect(Palette::slotFor(1, scatter: true))->toBe(1);   // azul
        expect(Palette::slotFor(4, scatter: true))->toBe(6);   // rojo, no naranja
        expect(Palette::slotFor(5, scatter: true))->toBe(8);   // verde
    });

    it('se niega a pintar una sexta serie en un scatter', function () {
        expect(fn () => Palette::slotFor(6, scatter: true))
            ->toThrow(InvalidArgumentException::class, 'deuteranopia');
    });
});

describe('las rampas: secuencial y ordinal', function () {
    it('cuantiza el valor en escalones, sin calcular un solo color', function () {
        // PHP no interpola colores. Reparte el valor en escalones y devuelve un número; el color
        // lo pone el CSS con un token. Si el servidor interpolara, el color volvería a viajar como
        // VALOR — y con él se iría el repintado automático al cambiar de tema.
        expect(Palette::bucket(0, 0, 100))->toBe(1);
        expect(Palette::bucket(50, 0, 100))->toBe(4);
        expect(Palette::bucket(100, 0, 100))->toBe(7);
    });

    it('no se sale de la rampa ni con valores fuera de rango', function () {
        expect(Palette::bucket(-999, 0, 100))->toBe(1);
        expect(Palette::bucket(999, 0, 100))->toBe(7);
    });

    it('con todos los valores iguales manda el escalón de arriba', function () {
        // No hay escala que hacer: todo está al máximo de lo que hay.
        expect(Palette::bucket(5, 5, 5))->toBe(7);
    });

    it('devuelve tokens, nunca colores', function () {
        expect(Palette::sequential(4))->toBe('var(--kore-seq-4)');
        expect(Palette::ordinal(2))->toBe('var(--kore-ord-2)');
    });

    it('el octavo escalón no existe', function () {
        // Por encima de siete el ojo deja de distinguirlos, y entonces ya no es una escala: es un
        // degradado bonito del que no se puede leer un valor.
        $mensaje = null;

        try {
            Palette::sequential(8);
        } catch (InvalidArgumentException $e) {
            $mensaje = $e->getMessage();
        }

        expect($mensaje)->toContain('deja de distinguirlos');
    });
});

describe('un color que no existe LANZA, en vez de dejar la serie invisible', function () {
    it('acepta los tokens de la paleta de datos', function () {
        // `chart-4` es lo primero que uno prueba, y hasta ahora se colaba tal cual al CSS:
        // `--kore-series: chart-4` no es un color válido, así que la serie NO SE PINTABA. Sin un
        // solo error. Medido en la demo: un gráfico de barras entero, invisible.
        expect(Palette::resolve(1, 'chart-4'))->toBe('var(--kore-chart-4)');
        expect(Palette::resolve(1, 'seq-3'))->toBe('var(--kore-seq-3)');
        expect(Palette::resolve(1, 'ord-7'))->toBe('var(--kore-ord-7)');
    });

    it('sigue aceptando los tokens semánticos', function () {
        expect(Palette::resolve(1, 'destructive'))->toBe('var(--kore-destructive)');
    });

    it('deja pasar un color CSS explícito', function () {
        expect(Palette::resolve(1, '#e11d48'))->toBe('#e11d48');
        expect(Palette::resolve(1, 'oklch(0.6 0.2 30)'))->toBe('oklch(0.6 0.2 30)');
        expect(Palette::resolve(1, 'var(--mi-color)'))->toBe('var(--mi-color)');
    });

    it('lanza con una errata, en vez de dejar el gráfico en blanco', function () {
        // Un color que no existe no debe dejar la serie invisible: debe decirlo.
        foreach (['destructiv', 'rojo', 'chart-9', 'blue-500'] as $errata) {
            $mensaje = null;

            try {
                Palette::resolve(1, $errata);
            } catch (InvalidArgumentException $e) {
                $mensaje = $e->getMessage();
            }

            expect($mensaje)->toContain('no es un color que el gráfico sepa pintar');
        }
    });
});
